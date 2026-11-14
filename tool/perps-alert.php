<?php
/**
 * SOL PERPS SCANNER — version cron + PHPMailer
 *
 * Tourne sans navigateur. Cron toutes les 15 min :
 *   /15 * * * * /usr/bin/php /chemin/vers/sol-perps-cron.php >> /chemin/vers/sol-perps.log 2>&1
 *
 * Installation :
 *   composer require phpmailer/phpmailer
 *   crontab -e  → coller la ligne ci-dessus
 *
 * Anti-spam : un mail n'est envoyé que si le signal CHANGE ou si
 * OPPORTUNITY_COOLDOWN_H est dépassé. État conservé dans sol-perps-state.json.
 */

// ============================================================
// 0. AUTOLOAD PHPMailer — chemin auto-détecté
// ============================================================
$autoloadCandidates = [
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];
$autoloadFound = false;
foreach ($autoloadCandidates as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        $autoloadFound = true;
        break;
    }
}
if (!$autoloadFound || !class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    die("❌ Composer autoload introuvable. Lance : composer require phpmailer/phpmailer\n"
        . "   (chemins testés : " . implode(' | ', $autoloadCandidates) . ")\n");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================================
// 1. CONFIG — tout s'édite ici
// ============================================================
const SYMBOL          = 'SOLUSDT';
const MIN_CONFLUENCE  = 3;      // nb de signaux alignés minimum (sur 5)
const MIN_GAP         = 2;      // écart minimal long vs short
const OPPORTUNITY_COOLDOWN_H = 6;   // renvoyer un mail si même signal > 6h
const STATE_FILE      = __DIR__ . '/sol-perps-state.json';
const LOG_PREFIX      = '[SOL-PERPS]';

$smtp = [
    'host'     => 'smtp.hostinger.com',
    'username' => 'contact@gael-berru.com',
    'password' => '**** **** **** ****',
    'port'     => 587,
    'secure'   => PHPMailer::ENCRYPTION_STARTTLS,
    'debug'    => false,
];

$mailTo = [
    'to'   => 'g.leberruyer@gmail.com',   // ← ton adresse de réception
    'from' => 'contact@gael-berru.com',
];

// Paramètres trade (identiques à la version web)
$trade = [
    'collateral' => 20.0,   // $
    'leverage'   => 3,
    'feePct'     => 0.05,   // taker par side, en %
    'hoursMax'   => 24,
];

// ============================================================
// 2. HELPERS
// ============================================================
function log(string $msg): void {
    echo LOG_PREFIX . ' ' . date('Y-m-d H:i:s') . " — $msg\n";
}

/** GET JSON avec cURL */
function apiGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'sol-perps-scanner/1.0',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($code !== 200 || $body === false) {
        throw new RuntimeException("API $url → HTTP $code $err");
    }
    return json_decode($body, true) ?? throw new RuntimeException("JSON invalide depuis $url");
}

function ema(array $values, int $period): ?float {
    if (count($values) < $period) return null;
    $k = 2 / ($period + 1);
    $e = array_sum(array_slice($values, 0, $period)) / $period;
    for ($i = $period, $n = count($values); $i < $n; $i++) {
        $e = $values[$i] * $k + $e * (1 - $k);
    }
    return $e;
}

function rsi(array $closes, int $period = 14): float {
    $n = count($closes);
    if ($n < $period + 1) return 50.0;
    $gains = 0.0; $losses = 0.0;
    for ($i = $n - $period; $i < $n; $i++) {
        $d = $closes[$i] - $closes[$i - 1];
        if ($d >= 0) $gains += $d; else $losses -= $d;
    }
    $al = $losses / $period;
    if ($al == 0) return 100.0;
    return 100 - 100 / (1 + ($gains / $period) / $al);
}

function atr(array $candles, int $period = 14): float {
    $slice = array_slice($candles, -$period);
    if (!$slice) return 0.0;
    $sum = 0.0;
    foreach ($slice as $c) $sum += $c['high'] - $c['low'];
    return $sum / count($slice);
}

/** Charger / sauver l'état (anti-spam mails) */
function loadState(): array {
    if (file_exists(STATE_FILE)) {
        $s = json_decode(file_get_contents(STATE_FILE), true);
        if (is_array($s)) return $s;
    }
    return ['lastSignal' => null, 'lastSentAt' => 0];
}
function saveState(array $state): void {
    file_put_contents(STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));
}

// ============================================================
// 3. INDICATEURS + SIGNAL
// ============================================================
function computeIndicators(): array {
    // Bougies 15m (100 dernières)
    $klines = apiGet('https://fapi.binance.com/fapi/v1/klines?' . http_build_query([
        'symbol' => SYMBOL, 'interval' => '15m', 'limit' => 100,
    ]));
    $candles = array_map(fn($k) => [
        't' => $k[0], 'open' => (float)$k[1], 'high' => (float)$k[2],
        'low' => (float)$k[3], 'close' => (float)$k[4], 'vol' => (float)$k[5],
    ], $klines);
    $closes = array_column($candles, 'close');
    $price  = end($closes);

    // Funding + mark price
    $f = apiGet('https://fapi.binance.com/fapi/v1/premiumIndex?symbol=' . SYMBOL);
    $funding = (float)$f['lastFundingRate'] * 100;
    $nextFundingTs = (int)$f['nextFundingTime'];

    // Open Interest historique (13 points de 15m = ~3h)
    $oiHist = apiGet('https://fapi.binance.com/futures/data/openInterestHist?' . http_build_query([
        'symbol' => SYMBOL, 'period' => '15m', 'limit' => 13,
    ]));
    $oiFirst = (float)$oiHist[0]['sumOpenInterest'];
    $oiLast  = (float)end($oiHist)['sumOpenInterest'];
    $oiChange = $oiFirst > 0 ? ($oiLast - $oiFirst) / $oiFirst * 100 : 0;

    // Variation 24h
    $idx = max(0, count($closes) - 97);
    $change24h = ($price - $closes[$idx]) / $closes[$idx] * 100;

    $atr    = atr($candles, 14);
    $rsiNow = rsi($closes, 14);
    $rsiPrev = rsi(array_slice($closes, 0, -3), 14);
    $rsiDelta = $rsiNow - $rsiPrev;
    $ema20 = ema($closes, 20);
    $ema50 = ema($closes, 50);

    $ind = [];

    // 1. EMA cross
    if ($ema20 && $ema50) {
        if ($ema20 > $ema50 * 1.0005)      $ind[] = ['EMA20/50', 1, 'croisement haussier'];
        elseif ($ema20 < $ema50 * 0.9995)  $ind[] = ['EMA20/50', -1, 'croisement baissier'];
        else                               $ind[] = ['EMA20/50', 0, 'plates'];
    }
    // 2. Momentum vs EMA20
    if ($ema20) {
        $ind[] = $price > $ema20
            ? ['Prix vs EMA20', 1, 'au-dessus (momentum haussier)']
            : ['Prix vs EMA20', -1, 'en-dessous (momentum baissier)'];
    }
    // 3. RSI extrême + pente
    if ($rsiNow < 35 && $rsiDelta > 2)      $ind[] = ['RSI', 1, sprintf('%.1f survente qui remonte (+%.1f)', $rsiNow, $rsiDelta)];
    elseif ($rsiNow > 65 && $rsiDelta < -2) $ind[] = ['RSI', -1, sprintf('%.1f surachat qui retombe (%.1f)', $rsiNow, $rsiDelta)];
    elseif ($rsiNow < 30)                   $ind[] = ['RSI', 1, sprintf('%.1f survente', $rsiNow)];
    elseif ($rsiNow > 70)                   $ind[] = ['RSI', -1, sprintf('%.1f surachat', $rsiNow)];
    else                                    $ind[] = ['RSI', 0, sprintf('%.1f neutre', $rsiNow)];
    // 4. Funding (foule)
    if ($funding > 0.05)      $ind[] = ['Funding', -1, sprintf('%+.4f%% — longs surpayés, squeeze short possible', $funding)];
    elseif ($funding < -0.05) $ind[] = ['Funding', 1, sprintf('%+.4f%% — shorts paient, pression acheteuse', $funding)];
    else                      $ind[] = ['Funding', 0, sprintf('%+.4f%% neutre', $funding)];
    // 5. Open Interest
    if ($oiChange < -3 && $change24h < 0)      $ind[] = ['Open Interest', 1, sprintf('%+.1f%% + prix baisse = shorts se liquident', $oiChange)];
    elseif ($oiChange > 3 && $change24h > 0)   $ind[] = ['Open Interest', -1, sprintf('+%.1f%% + prix monte = extension fragile', $oiChange)];
    elseif ($oiChange > 3 && $change24h < 0)   $ind[] = ['Open Interest', 1, sprintf('+%.1f%% + prix baisse = nouveaux shorts, cible de squeeze', $oiChange)];
    else                                       $ind[] = ['Open Interest', 0, sprintf('%+.1f%% plat', $oiChange)];

    $long = $short = 0;
    foreach ($ind as [, $v]) { if ($v > 0) $long++; elseif ($v < 0) $short++; }

    $total = count($ind);
    $atrTooLow = $atr < $price * 0.002;
    $signal = 'wait';
    if (!$atrTooLow && $long >= MIN_CONFLUENCE && $long - $short >= MIN_GAP)       $signal = 'long';
    elseif (!$atrTooLow && $short >= MIN_CONFLUENCE && $short - $long >= MIN_GAP)  $signal = 'short';

    return compact('price', 'funding', 'nextFundingTs', 'oiChange', 'change24h',
                   'atr', 'rsiNow', 'ema20', 'ema50', 'ind', 'long', 'short', 'total', 'signal');
}

// ============================================================
// 4. MAIL
// ============================================================
function sendSignalMail(array $smtp, array $mailFromTo, array $d, array $trade): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->Port       = $smtp['port'];
        $mail->SMTPSecure = $smtp['secure'];
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = $smtp['debug'] ? 2 : 0;

        $mail->setFrom($mailFromTo['from'], 'SOL Perps Scanner');
        $mail->addAddress($mailFromTo['to']);

        $isLong = $d['signal'] === 'long';
        $dir    = $isLong ? 'LONG 🟢' : 'SHORT 🔴';
        $mail->Subject = "🚨 SOL Perps — opportunité $dir — \$" . number_format($d['price'], 2);

        $price = $d['price']; $atr = $d['atr'];
        $sl = $isLong ? $price - 1.5 * $atr : $price + 1.5 * $atr;
        $tp = $isLong ? $price + 3 * $atr : $price - 3 * $atr;
        $posSize   = $trade['collateral'] * $trade['leverage'];
        $fees      = $posSize * ($trade['feePct'] / 100) * 2;
        $fundingEst = abs($d['funding'] / 100) * $posSize * ($trade['hoursMax'] / 8);
        $breakeven = ($fees + $fundingEst) / $posSize * 100;
        $nextH = max(0, (int)ceil(($d['nextFundingTs'] - time()) / 3600));

        $rows = '';
        foreach ($d['ind'] as [$name, $v, $desc]) {
            $badge = $v > 0 ? '▲ LONG' : ($v < 0 ? '▼ SHORT' : '— neutre');
            $rows .= "<tr><td><b>$name</b></td><td>$badge</td><td>" . htmlspecialchars($desc) . "</td></tr>";
        }

        $mail->isHTML(true);
        $mail->Body = "
        <div style='font-family:monospace;background:#0b0d12;color:#e8eaf2;padding:24px;border-radius:12px;max-width:640px;'>
          <h2 style='color:" . ($isLong ? '#34d399' : '#ff7a8a') . ";'>OPPORTUNITÉ $dir — confluence "
            . ($isLong ? $d['long'] : $d['short']) . "/{$d['total']}</h2>
          <p style='font-size:1.1em;'>Prix SOL : <b>\$" . number_format($price, 2) . "</b></p>
          <table style='border-collapse:collapse;width:100%;font-size:0.9em;'>
            <tr style='color:#8b90a5;'><th align='left'>Signal</th><th align='left'>Sens</th><th align='left'>Détail</th></tr>$rows
          </table>
          <hr style='border-color:#232839;'>
          <p><b>Plan de trade</b> (collatéral \${$trade['collateral']} · levier {$trade['leverage']}x · taille ~\$$posSize)</p>
          <ul>
            <li>Entrée : <b>\$" . number_format($price, 2) . "</b></li>
            <li>Stop Loss : <span style='color:#ff7a8a;'>\$" . number_format($sl, 2) . "</span></li>
            <li>Take Profit : <span style='color:#34d399;'>\$" . number_format($tp, 2) . "</span> (R:R 2:1)</li>
            <li>Breakeven : le prix doit bouger de <b>" . number_format($breakeven, 2) . "%</b> pour couvrir frais + funding</li>
            <li>Prochain funding dans ~{$nextH}h (" . ($d['funding'] > 0.05 ? 'longs paient' : ($d['funding'] < -0.05 ? 'shorts paient' : 'neutre')) . ")</li>
            <li>⏱️ Clôture forcée : dans {$trade['hoursMax']}h max</li>
          </ul>
          <p style='color:#8b90a5;font-size:0.8em;'>Scanner automatique — pas un conseil financier. Vérifie le graphique avant d'entrer.</p>
        </div>";

        $mail->AltBody = "OPPORTUNITÉ $dir — SOL $" . number_format($price, 2)
            . "\nEntrée $" . number_format($price, 2)
            . " | SL $" . number_format($sl, 2)
            . " | TP $" . number_format($tp, 2)
            . "\nClôture dans {$trade['hoursMax']}h max.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        log('❌ Mail échoué : ' . $mail->ErrorInfo);
        return false;
    }
}

// ============================================================
// 5. MAIN
// ============================================================
try {
    $d = computeIndicators();
    log(sprintf('Prix $%.2f | RSI %.1f | funding %+.4f%% | ΔOI %+.1f%% | long %d/%d short %d/%d → %s',
        $d['price'], $d['rsiNow'], $d['funding'], $d['oiChange'],
        $d['long'], $d['total'], $d['short'], $d['total'], strtoupper($d['signal'])));

    $state = loadState();
    $shouldSend = false;

    if (in_array($d['signal'], ['long', 'short'], true)) {
        $hoursSince = (time() - $state['lastSentAt']) / 3600;
        if ($state['lastSignal'] !== $d['signal'] || $hoursSince >= OPPORTUNITY_COOLDOWN_H) {
            $shouldSend = true;
        }
    }

    if ($shouldSend) {
        if (sendSignalMail($smtp, $mailTo, $d, $trade)) {
            log("✅ Mail envoyé (signal {$d['signal']})");
            $state = ['lastSignal' => $d['signal'], 'lastSentAt' => time()];
        }
    } else {
        log($d['signal'] === 'wait'
            ? 'Pas de signal. Rien envoyé.'
            : "Signal {$d['signal']} déjà notifié récemment (cooldown " . OPPORTUNITY_COOLDOWN_H . "h).");
    }

    saveState($state);
} catch (Throwable $e) {
    log('❌ ERREUR : ' . $e->getMessage());
    exit(1);
}