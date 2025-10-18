<?php
// purchase_points.php
require_once 'config.php';
require_once 'auth.php';

class PointPurchase
{
    private $stripe;

    public function __construct()
    {
        \Stripe\Stripe::setApiKey('sk_test_ta_cle_stripe');
        $this->stripe = new \Stripe\Stripe();
    }

    public function createCheckoutSession($userId, $packId)
    {
        try {
            // Récupérer le pack
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM point_packs WHERE id = ? AND is_active = true");
            $stmt->execute([$packId]);
            $pack = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pack) {
                return ['success' => false, 'message' => 'Pack non trouvé'];
            }

            // Créer la transaction en attente
            $stmt = $db->prepare("INSERT INTO point_transactions (user_id, points_amount, amount_eur, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$userId, $pack['points_amount'], $pack['price_eur']]);
            $transactionId = $db->lastInsertId();

            // Créer la session Stripe
            $checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => $pack['name'] . ' - ' . $pack['points_amount'] . ' 💎',
                                'description' => 'Points pour l\'éditeur 3D Scroll Animator'
                            ],
                            'unit_amount' => $pack['price_eur'] * 100, // Stripe utilise les centimes
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => 'https://ton-site.com/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://ton-site.com/payment_cancel.php',
                'metadata' => [
                    'user_id' => $userId,
                    'transaction_id' => $transactionId,
                    'pack_id' => $packId
                ]
            ]);

            return ['success' => true, 'sessionId' => $checkout_session->id];

        } catch (Exception $e) {
            error_log("Erreur création session: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur système'];
        }
    }

    public function handleWebhook()
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $endpoint_secret = 'whsec_ton_webhook_secret';

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            exit();
        }

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $this->fulfillOrder($session);
        }

        http_response_code(200);
    }

    // purchase_points.php - CORRIGÉ
    private function fulfillOrder($session)
    {
        $userId = $session->metadata->user_id;
        $transactionId = $session->metadata->transaction_id;

        // CORRECTION : Récupérer le pack pour connaître le nombre de points
        $db = Database::getConnection(); //Database::getConnection(); - or - getDB(); ???
        $stmt = $db->prepare("SELECT points_amount FROM point_packs WHERE id = ?");
        $stmt->execute([$session->metadata->pack_id]);
        $pack = $stmt->fetch(PDO::FETCH_ASSOC);
        $pointsAmount = $pack['points_amount'];

        try {
            $db->beginTransaction();

            // Marquer la transaction comme complétée
            $stmt = $db->prepare("UPDATE point_transactions SET status = 'completed', payment_intent_id = ? WHERE id = ?");
            $stmt->execute([$session->payment_intent, $transactionId]);

            // Ajouter les points à l'utilisateur
            $stmt = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
            $stmt->execute([$pointsAmount, $userId]);

            $db->commit();

            // Mettre à jour la session si l'utilisateur est connecté
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                // CORRECTION : Utiliser self::getPoints ou refaire une requête
                $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['user_points'] = $user['points'];
            }

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erreur fulfillment: " . $e->getMessage());
        }
    }
}