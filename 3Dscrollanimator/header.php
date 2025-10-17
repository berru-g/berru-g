<?php
// header_start.php
require_once 'config.php';
require_once 'auth.php';
require_once 'PointsManager.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Scroll Animator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ====== HEADER MODERNE ====== */
        :root {
            --white: #f1f1f1;
            --dark: #151517;
            --grey: #1b1b1c;
            --grey-light: #2c2c2e;
            --primary: #ab9ff2;
            --rose: #cba6f7;
            --border: #dcdcdc;
            --success: #60d394;
            --error: #ee6055;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--dark);
            color: var(--rose);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .modern-header {
            background: rgba(21, 21, 23, 0.95);
            /*#0a0718*/
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 0.8rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .header-left img {
            height: 40px;
            width: auto;
            border-radius: 100%;
        }

        .logo {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo-icon {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .nav-icons {
            display: flex;
            gap: 1rem;
        }

        .nav-icon {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            text-decoration: none;
            color: var(--white);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .nav-icon:hover {
            background: rgba(171, 159, 242, 0.1);
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .nav-icon.active {
            background: rgba(241, 241, 241, 0.14);
            color: var(--white);
        }

        .nav-icon i {
            font-size: 1.1rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .auth-buttons {
            display: flex;
            gap: 0.8rem;
        }

        /*
        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--dark);
        }

        .btn-primary:hover {
            background: var(--rose);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: var(--white);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
*/
        /* Menu utilisateur */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--rose));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            min-width: 200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .user-menu:hover .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem;
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .user-dropdown-item:hover {
            background: rgba(171, 159, 242, 0.1);
        }

        .user-dropdown-item i {
            width: 20px;
            color: var(--primary);
        }

        .user-dropdown-divider {
            height: 1px;
            background: var(--border);
            margin: 0.5rem 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modern-header {
                padding: 0.8rem 1rem;
            }

            .nav-icon span {
                display: none;
            }

            .header-left span {
                display: none;
            }

            .auth-buttons span {
                display: none;
            }

            .nav-icon {
                padding: 0.6rem;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }
        }

        /* system de points */
        .user-points {
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 8px;
            font-weight: bold;
        }

        .points-info {
            background: var(--grey-light);
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
            text-align: center;
            /*border-left: 4px solid var(--primary);*/
        }

        .points-cost {
            color: var(--rose);
            font-weight: bold;
        }
    </style>
</head>

<body>
    <header class="modern-header">
        <div class="header-left">
            <a href="landing.html" class="logo">
                <img src="../img/mascotte-code.png">
                <span>3DScrollAnimate</span>
            </a>

            <div class="nav-icons">
                <a href="index.php"
                    class="nav-icon <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-pen"></i>
                    <span>Edit</span>
                </a>
                <a href="gallery.php"
                    class="nav-icon <?= basename($_SERVER['PHP_SELF']) == 'gallery.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-folder"></i>
                    <span>Explore</span>
                </a>
            </div>
        </div>

        <div class="header-right">
            <?php if (Auth::isLoggedIn()): ?>
            <div class="points-info">
                <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <strong><span id="current-points"><?= $_SESSION['user_points'] ?? 200 ?></span></strong> 💎
            </div>
            
                <div class="user-menu">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                    </div>

                    <div class="user-dropdown">

                        <a href="dashboard.php" class="user-dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>Profil</span>
                        </a>



                        <div class="user-dropdown-divider"></div>

                        <a href="?logout" class="user-dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Log Out</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Log In</span>
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        <span>Sign Up</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main>