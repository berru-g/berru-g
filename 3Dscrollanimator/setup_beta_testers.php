<?php
// setup_beta_testers.php - Script de setup pour beta testers
require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = getDB();
    
    echo "🎮 Setup des beta testers...\n\n";
    
    // 1. Reset points
    $stmt = $db->prepare("UPDATE users SET points = 200 WHERE id > 1");
    $stmt->execute();
    echo "✅ Points reset pour tous les utilisateurs\n";
    
    // 2. Points spéciaux beta testers
    $betaTesters = [
        2 => 1000, // Super beta tester
        3 => 750,  // Beta tester actif  
        4 => 500,  // Beta tester régulier
        5 => 350   // Nouveau beta tester
    ];
    
    foreach ($betaTesters as $userId => $points) {
        $stmt = $db->prepare("UPDATE users SET points = ? WHERE id = ?");
        $stmt->execute([$points, $userId]);
        echo "✅ User $userId → $points points\n";
    }
    
    // 3. Nettoyer anciennes transactions
    $stmt = $db->prepare("DELETE FROM point_transactions WHERE payment_intent_id LIKE 'pi_test_%'");
    $stmt->execute();
    echo "✅ Anciennes transactions de test nettoyées\n";
    
    // 4. Insérer nouvelles transactions
    $transactions = [
        [2, 500, 19.90, 'completed', 'pi_test_beta_2_1', date('Y-m-d H:i:s', time() - 10*24*3600)],
        [2, 100, 4.90, 'completed', 'pi_test_beta_2_2', date('Y-m-d H:i:s', time() - 5*24*3600)],
        [2, 500, 19.90, 'completed', 'pi_test_beta_2_3', date('Y-m-d H:i:s', time() - 1*24*3600)],
        [3, 100, 4.90, 'completed', 'pi_test_beta_3_1', date('Y-m-d H:i:s', time() - 8*24*3600)],
        [3, 500, 19.90, 'completed', 'pi_test_beta_3_2', date('Y-m-d H:i:s', time() - 3*24*3600)],
        [4, 1500, 49.90, 'completed', 'pi_test_beta_4_1', date('Y-m-d H:i:s', time() - 15*24*3600)],
        [4, 500, 19.90, 'completed', 'pi_test_beta_4_2', date('Y-m-d H:i:s', time() - 7*24*3600)],
        [4, 100, 4.90, 'completed', 'pi_test_beta_4_3', date('Y-m-d H:i:s', time() - 2*24*3600)],
        [5, 100, 4.90, 'completed', 'pi_test_beta_5_1', date('Y-m-d H:i:s', time() - 4*24*3600)],
        [5, 500, 19.90, 'completed', 'pi_test_beta_5_2', date('Y-m-d H:i:s', time() - 1*24*3600)]
    ];
    
    $stmt = $db->prepare("INSERT INTO point_transactions (user_id, points_amount, amount_eur, status, payment_intent_id, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($transactions as $transaction) {
        $stmt->execute($transaction);
        echo "✅ Transaction pour user $transaction[0] : $transaction[1] points\n";
    }
    
    echo "\n🎉 Setup terminé avec succès !\n\n";
    
    // Afficher les stats
    $stats = $db->query("
        SELECT 
            COUNT(DISTINCT user_id) as acheteurs_uniques,
            COUNT(*) as total_achats,
            SUM(points_amount) as total_points_vendus,
            SUM(amount_eur) as chiffre_affaires
        FROM point_transactions 
        WHERE status = 'completed'
    ")->fetch();
    
    echo "📊 STATS FINALES :\n";
    echo "Acheteurs uniques : " . $stats['acheteurs_uniques'] . "\n";
    echo "Total achats : " . $stats['total_achats'] . "\n"; 
    echo "Points vendus : " . $stats['total_points_vendus'] . " 🪙\n";
    echo "Chiffre d'affaires : " . $stats['chiffre_affaires'] . " €\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}