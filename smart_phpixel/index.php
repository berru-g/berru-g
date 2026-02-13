<?php
//require_once __DIR__ . '/smart_pixel_v2/includes/config.php';

//$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
//$total = $pdo->query("SELECT COUNT(*) FROM user_sites")->fetchColumn();
//$remaining_spots = max(0, 100 - $total);
?>

<!DOCTYPE html>
<html lang="fr" prefix="og: https://ogp.me/ns#">
<!-- 
                                                                                                                                                                                                                                                                                                                                                                                            
                                                                                                                            -+:                                                                         
                                                                                                                          ++=::                                                                         
                                                                                                                        -+--:..                                                                         
                                                                                                                      --=:::..                                                                          
                                                                                                                    .=:-::.::.                                                                          
                                                                                           -+*+.                   -==::-::.:                                                                           
                                                                                          =--:::                 .*====-::::                                                                            
                                                                                         +=:::.:                +==:-----:--                               -                                            
                                                                                        :=:=+:::              .*+--+++-::::.                            -=++::                                          
                                                                                       :++++=:::             ===---=--::-::                         .**-:-:+=:                                          
                                                                        +*+=-          =+--:::::            ====-**-=-:-=-:                        =++=:-.::..                                          
                                                                      +==----.       :+=+*+-::::          :++==---:--=:-::.                     :+=-:::.---::.                                          
                                                                     ---+:::::       +*====+=--=:        ++++++--==-:::::.                     +-:-::::-:::::                                           
                                                                    ==+=--+=:-      ===*===+-==---     .+++====--::--=-:-.                  :+=:--::==-::::..                                           
                                                                   ==+=---:-::     ==-=*-===--=-**=  :=*++==---==--=-::::                 ====::-:-:+++::-.                                             
                                                          =+++.   --+==----+-+=- -*===+--=-=--=*-+==+#=*--------:----=:==             .+=+=::--=====-::...                                              
                                                         +===*+===*===--:::-=+=--==+-==--++==--=+--=-====-=-===+---==-:::          -=+==-=:---=-:-:::..:                                                
                                                       :=-=++=*=++====-=+==+--==-=====++==--=:-+-----=-+---=+=---:=:::::-     :+++====-:-::::::-:---+..                                                 
                                                      :+==++=*==-+-====-==:-===-=++=-===++==----+=====--=+====----=-:--=+*#===+====-==---:----:==:-..                                                   
                                                     .--=**++-=---===---=-=---:====-+=-+---*=---=-==-+=-=--===-=:::--===*#*===+--=--:-=::-::-:::::::                                                    
                                                    ----==--++*=**==--+-+=-*==:=-=-===*+++=+-+=++--==-=*--+*+-=-:---=-+===--==+-=---:=-+=--:-:=--:.                                                     
                                                    =-=*+=====+=--=+=+-==:-==:-*+-+=+=-+++++=+=+==--=-++--==+---------+-=---===-+=---+===----=::.                                                       
                                                    -*+=*+**#+=+++-++*++====-+=--=---=-==--=---=---+--=---=-------==-==--=-+----==-==-=------::.                       :--:.:                           
                                                    -++#-+=---==-+-+===--:-----=-==**+=+*=++=+=+=-=====-===--=-=-=---==----=====-=-==--:=:::::.                  .-=-:-::::::                           
                                                    -+*++-+*=*+=+==--==:=====**=*+*=+-==+-*--=--=:----==-=-+=--=--+---:=-----==-=::--+++--=====-=+-=*==+==+++-:=:::.--==-:::=.                          
                                                    :==+=%++-----::--+++=++++**+=--:----=-+=+=--==+==++======+-=+=--=-=-=-:=--==+===:=:=:::--==+==-:+-+==::--+=----:=---=-=+--                          
                                                  .=+*+*=+=---+-++-=-=---:::------=----=-=-+=+=+=-=*+#+==*++===+=-=-=---:--=-=+===---==::=:===:-:-:-=-:-:=-::-:---:-:-::=..:..                          
                                                 -=*=====--=*+=+=--:---==++==+==--=-+=-=+-=*++*=*#=*+**++=+=++==+-=--=-:-:----=---::-==::+=:-=-=:-:::-:::::-+-::..:.-..:::=-.                           
                                                :++++=-=#++*-=++*=----:-====+=======%***+##+*=*%++#+++#**+-+++*=+===+=---+-*:----=-:-+:--:=-+=---==:-----:-::::-=:=-:-+*:=+..                           
                                               :=+-+=+*=----*=----++-::---=-==+===-%%**#+*+##%*##+*+++==*==+==--==+***%%*--:--=--::--::--:=-=-+=--:=:-=-:::=----:--=-=:::...                            
                                              :=+--*=+-:--:-:--+=*+=--=---=-+--=#*%##%*##*%%%#%#%@%%%###**#*+--=--=--:-=-:=:==---::=:-----=---=:::-------:::--=:=-::..:.::..                            
                                             -+-=**--:++==-=+------+--=---====+#####**%#%##%@%%@%#+++=+#*=++--==--:-=-=:::--=--=+--==-:-------:----=-:-==::-:--=-:.::::--:-=-.:                         
                                             =-+++-:=-::----*=*-+--==--===+*+=#####*%#%#@%+=*#%%@@@@@@@@%##=-:=:--::----:-::-----+::--:-::-=---=:---=::::::..:---+.:-:=:::-:-::::.                      
                                             =-+=---+*--=--=-===+-+++++-*%*+*###*##++*=+++*#%%%*%@#%%%%%%%%%%%#*:-:-:------==--==:==::==:--:-:-:-::=::---+*-:-+-:-.=:--:.:::=.--..                      
                                             :--+++*=*=-=-+=:-+====#+=++*+==##*++=*=*=*=**=+%*+-+#****##%###%##%###*---::------=----:=-:=---=:::--==-:::--=:==-=:::.::=.::=.-:::..                      
                                              --=---+=+=--*++=--:::::::*==+*****+++==*#*++**+*=+-#=+=*+=++*#**##*#####*:-:-+:-=-----=-:-----:-:::-:---=-:..:--:.:::=-::...:....:.                       
                                               +%#**-*-=:+-:::::-:....-:+*=-+-=+-=-===:::::-+-=-=:***#+++++***********#**+:::---=-=-=-=-:::-::=--:--=:::-::-:=-:::=-::.:-.:.-:=.                        
                                            #@@%%%%##*---:..:....-=--+*===-:----=-:::::::::::::-=-+=*##*#+*+*****+*++****+=:::-:--=::-::::-:::::..:-:::::::..:::.:--:=:=-:-:-:.                         
                                          @@@%%###***####*++====+-==-:....:.:-----:::::*++-==:::-+=++##%************###****+--::=:---:-=-:-::::::----:::::::::..:::-=:-:.....                           
                                        -@%%##****+++*++++++*+-:=-==:+--::--+==------:-.....=-:+-=+--=-**#************#####***-::::--::---::-:::::-.::::-..-::::::.::::::.                              
                                       .%%*:            ::---==-==--:===++=-==*+*=-===-:...==:=--==+-:-:-++****##*##**######****+.:-:-:--:--:-::-::-..::::-.::-.=.:.:....                               
                                                                .:---+==-=-#*++=*====*++==--==::-++=-::.::+=+***#*#####*#####***++=.:.:::::-:=--:::-::::.:-:::-:::::...                                 
                                                                      =:+++=*=*+=+++*-=---::-*+==++-:::::+=++******###**##***#***++==..:::::::::::-:..::::::-:..::..                                    
                                                                       .-:-==--===+**+*+****+++=#=-+:+*#%#####%##++****###*##****+=++=:.:-:-.::::..::.:.:-::::::::                                      
                                                                         +=::::-+=+*#*++====+==+-=--####%##%#######+++****###*###=+**==+.....::::.:::.:::::::                                           
                                                                          +::::-=:-#=+##====*+=-==+*#*#*###%########*+*+++**=+****+=++===+.  ....                                                       
                                                                        +%*#::::=:::=-+==++==--:-=#**+#*###*##*%#%*#+-+=*++=**++**=+++++===-                                                            
                                                                     =#%*+*--=:-:--==--::---=--===++=-===+=-=---:=+-::==+++++*+#**+*+==*+===-                                                           
                                                              :######**+=-----=-=-*===++------=--=++=-*+*==::::.-:.:--++*+******+*****+=====+-                                                          
                                                         :#%%%%#*==*=----------=-==*===-=-=-:---==---%=+==:.=:::::..:*+++*++**********#**=+++*                                                          
                                                     +@@%%#+++*+=-:::-:-:--:-===:---:=-----:--==---%+-=-+::::-+::.:-=++=***+#***#**********+***.                                                        
                                                 .@@%#**++====+=-*-::::-==:---=-:-:+:::-::::-:::::#==*-::=:=-+-:=-:-+****#**#****##*********++++*                                                       
                                              .%%##*++++====----:::::..::=::::::::::::::::::::::+*+==-=:--=::--*++=+*+*#*#*#****+*#***********=====                                                     
                                             %%#**++==----:::-:-*-:::.::-+::-:-:::::-::::-:::-:#+--==---=++====***::##*#***#****+*****++*****#**++++-                                                   
                                           %%#*++=:         =*=-:::::.:---:---:=--:--:--::::::%#===+--++==+=+=++*+:++*##**#***+*******=*+****###**++=*=                                                 
                                                            =-::::::::::-::::::=+--+*-:-:-=:-##*#++=-====+++--=*+=****###+#*##*****+***#******##***+=+**:                                               
                                                           .=-:::::::::--=::.:-::=-----:::::+#%+==#+===++=:=*#+**=######**+##****#***++**#*##**####****+*+:                                             
                                                           :--::::::::-+=-::.:::-:::-=::-:::#+++#**+*+=-==-+***=--*##*##********+*#****+*##**####%##=*+**=++                                            
                                                           ---:::::::=:=--:::...:::--:-+-:::+*==*+==-++=-=-==+=*:+*##*###**+*+*###*#**##*#*#=##**###***#**+==+                                          
                                                           .-=:::::::+-==--::.::::--::-+=:::*#==##==*-+--=+#*++::*+***##*+*##++########**##**+*##**##***+++==-=+-                                       
                                                            .+-:::::+-++-::::..:::::::-==:::+=##**=+---+%#*+-+-:+=*##**##***##++***#####**###+***#*##**+****===-==-                                     
                                                            -=::-::-+=--+:--::.::-----:-+:.:#+#==---+##*+=-==+=:****#*+###**#**==*+**#**#**###++*****##*******++===+=                                   
                                                            =-:::::++=+--::-::..:::-:--=-::.#+#***-%%#+=+*=-=++=##****=###**#***+**###**#**+##*+++***+*+**+*++**+*+-+*=                                 
                                                           +=-:::::+=+=--:--::::::::--:=-::.==---*++-=*+--::+=:+####**+#*=####**++**##*+*****##**++***++***+**=++****-=+-                               
                                                          ==:::::::=*===-:::::::::-:::==*:::-*:+*+**+-=-=-====++***###+**####*+*+*+*##****#***##***+*+*++****-*++++##+===+.                             
                                                         +-:::::::-++-+--:-:::::::::--=+-:::.+--=-:=++=-:--=+#+*#*###***+#*##*+=****#*#+*#*#+*+*#+**++***+*+*#*+*+=++=*+-=++                            
                                                        --:::::::==*+-+=-:::::::::::::*--::...:-:---==:=:--=-=##*#*##***+####*+:=*##+#*#*****+=**#*#*++*+*+++*-#***==+*+**+=+:                          
                                                        ---+--=+==+*+----:--:::::::=--++::::...-=++---::::-==+-**#*#***+*+###***=*+**#####*++++++***+***++**++=+****+=++=+*#*++                         
                                                        -=++=++=+++=---:::::::::::-:-=*-::::....:==-=-::::-=+**********+*##%##***#*##*#*###****=**#*#**#++***=*++***+*=-+*++***+=                       
                                                        ===+*=++**-===-:::::::::::::-*--::::....::+--:-:=--=-*#**=***########**##*#**#*####*+++++******-*#++*****+*****+*++++**++=:                     
                                                        .+*+=+=*+==-==-:=:::::::::::-*=::::......:.=-::-==+*++*+#**=########**#####*%**%*###+=****+**+*+*#+=+**=*#***+*#*+*=+***+++=                    
                                                        :+=**+=+*=-:=-:::::::::::::-*=-:::::......::--+=+=+*#*=****####*#*##+###=#*#**#*#%#=#**++**#*#+*+*=+*+*+*+**++=***+***++*=++*-                  
                                                        :=+++-==-+----:-:::::::::-:+*-::::::......::-*++==+*=*=*+**#*#+#####*###+*#**###***###++*#**#**++**+*+**+++**#*++=****=++++==*+                 
                                                         .-=++=-=-=::+-:::::::::-:-=+-::::::......:::*-=+**##*#***#+*##*##**##+####+#*##*%#%###**++*##-*+=**-****++****#+++=***=+++***+*                
                                                         .++-+=+=-=::--:::::::::::++-:::::::...:..:--:-+++#=#*#+#*####*#*##*###*##*##**##*+#**#=**+**#***#***+*=***++-***##*++*++**#*#*++               
                                                          -*+==-:--::--:::::::::::=--::::::...:.:::.--+-=****##*##**###**###**##*##**##*##**###++##*-=+*=*****+*****=++*+**+*+++****##*+++.             
                                                           -+===--::::--:::::::::-=-:::::::-::-::..+=++##**#=#*###**###*###****###****-##%*++#+*=*#+=:+*=+*****+**+*#+++++**#*+++*+*=+**++=-            
                                                            ---::::::::--::::::::==-::::::. ::...=.:=**#**=#*#*#*######*###*#*######****##*++++*++=*++-+%*=+***#*****#*-*++++*+++*+*++*#*++=+           
                                                           :+=-::::::::--::::::::=-:::::::.  :.-=.:+*#*+*+=###*###*###*=####**######***###*+=++=*+*+*+.=*#****##*+******+#*+*+**++***+++###*=+:         
                                                            .:=:::::::::-:::::::-=--::::::.  :...:+=+*+++=*+#*######*****##***#####***#+##***==+:*#**#=***#**#***+**+**+#*+***++**+*+*+*+***#*=-        
                                                              =-:::::::--:::::::=--:::::::   +:::+=:*#****##*####*+*##*+#%##**##*##**+**#*+**=+*=**#=##+#**#*+***#******+**+#***=+=-+***#*+*+=++=       
                                                              .-:::::--=----::::=-:::::::.  :-=:+==+#=##*+#%*#+#*#####*-##*#**##+*#****-*###************++*+**********+*+****#*****+++****#**+=++=      
                                                               .----:-+=-==:::::+-:::::::  .-==*==:=+-*#***###***#-#%#####**#*#####****+********#++*+*#*+=+++**=**+****+**+***=**+=*=+=++*#**++=+++     
                                                                 :------+--::::---::::::: .  *=+=++*==*##*#+##*+*#=#%*#####*-####**+*******=*++**+++**+**++=**+***##**+*+++=+**=**+**+*==++***+===+=    
                                                                  .-=---=-:-:::--:::::::-.   +=*:=++=:#++#*###**#%###*%#####=#+#+#*###**+****+*=+++=***#************#**+*++++***=*+*=+++++*+*+++++===.  
                                                                   .---:--:-:::-::::::::    -==-:+-.=*#**#*####*+##%%=##%%*#######**#*+*=+**-***+=++*****+*+:**+***+**#**=++*****=+*+*+++*:*++==*+++==: 
                                                                     .:=--::::::::::::.    .=+--=+*:+##**#%#=#*##*+#####%%##%+#*#**##**+*-+**++*+==+-*+*++*+++*++*##+*****+**-+**++++**+***-*++=*+=++-+=
                                                                       .--:::-:---::::     =+--=++*--=+*######%#*#*#%%#%#%*######%#*##***-+**+++-==++=+***+#*+*+++**#****+**+*++***+++**++**=*+=-*-=++-=
                                                                         .::::--=-:::     :+-=-=:*+****#%%*####*##=#%%###%**#**###+##**+*+=+*+:++==*+-++*+++*+:+*++****-**+*++*+-*#******++**=+**=*-+++=
                                                                            .::==--:     :=+=-==-=*-+*#*%##*###%#*###%###%###*=#*#****=-===*++++=+=+=+==*+-++*++=*******=*++*++*+:******+++=+****#+*+++=
                                                                                         =+=++-+#+*####%%+*###%#**+#######*#**=**##+*#*+=*+-+*+-=-===+:++*==+++*++*+*****=*+**+***+******.+++****+#+++==
                                                                                        ++-++++=++**#%%*%**#*#****=#-#####+**+**##*-**+***+-++++-==+======++-+*++=++*+****:+=++-*+-+***+**==++**=*=##-*+
                                                                                       =--+*++*+#+%*#%=@%%+#%#*+++*#+*####*#*+***###*##*+*+++++*+====+=:==+++:+**+-+++++**+:=++++++++=*+***+-*=++:*=##:*
                                                                                     .+===+--:+#*%+#*=*%####*%*=+***#*#+#+##*=+*=###***+-+++++=+++==+===-+=+=+-++++=++++++++++++=+***+*****=+*+==+*****-
                                                                                    ===-++=*=-=-%+*=*%%%#####=*=+*##*#+##*+**=+*##**+*#*+*++*-+++-=+====:++===+-++*=:++==++++-+++**+=*#****+=+:**+*+**+#
                                                                                   ==+++=+*+++-+%##+%%%#%=####++###***=*##**#*+++*****=++-++*+++++===+===-=+==++-+++==++++++++:==++**=+++*++*+*:*==*=***
                                                                                  -++++=++*:*=*#*%+##%###*#*+#**###*#*###=#**-**+*++*****+=***-+++++=+===--==+++=++++:+***+++++=+=+***+****+*+*****-+*+*
                                                                                 -**+=-*+*#***+**#+*#%#*###*=*=**####**##:***+*++=+***++*++*+**=+*++++==+==+++==+++**+-+****++++++-+***+****++***.**+*++

                                                                                ============================================
                                                                                    Project : Analytics Souverain
                                                                                    Developed by : https://github.com/berru-g/
                                                                                    First Commits on Nov 21, 2025
                                                                                    Version : 1.2.4
                                                                                    Copyright (c) 2025 Berru
                                                                                ============================================
-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pixel Analytics : Alternative Française Google Analytics | RGPD Garanti</title>
    <meta name="description" content="Remplacez Google Analytics par Smart Pixel, la solution analytics souveraines : Dashboard simple, conforme RGPD, installation 2min. Premier dashboard gratuit.">
    <meta name="keywords" content="alternative google analytics français, statistiques site web, analytics rgpd, dashboard simple, tracker visiteurs, analytics open source, analytics français, remplacer google analytics, analytics souverain, données france">
    <link rel="canonical" href="https://gael-berru.com/smart_phpixel/">
    <meta property="og:title" content="Smart Pixel : Alternative Française à Google Analytics">
    <meta property="og:description" content="Dashboard analytics simple et RGPD-compliant. Remplacez GA4 en 2 minutes.">
    <meta property="og:image" content="https://gael-berru.com/img/smart-pixel.png">
    <meta property="og:url" content="https://gael-berru.com/smart_phpixel/">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <link rel="stylesheet" href="./RGPD/cookie.css" hreflang="fr">
    <script data-sp-id="SP_79747769" src="https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/tracker.js" async></script>

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Smart Pixel Analytics - Alternative Google Analytics">
    <meta name="twitter:description" content="Solution analytics française, simple et conforme RGPD">

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@graph": [{
                    "@type": "SoftwareApplication",
                    "name": "Smart Pixel Analytics",
                    "applicationCategory": "BusinessApplication",
                    "operatingSystem": "Web",
                    "description": "Alternative française à Google Analytics, conforme RGPD, dashboard simple",
                    "offers": {
                        "@type": "Offer",
                        "price": "0",
                        "priceCurrency": "EUR",
                        "availability": "https://schema.org/InStock"
                    },
                    "aggregateRating": {
                        "@type": "AggregateRating",
                        "ratingValue": "4.8",
                        "reviewCount": "57",
                        "bestRating": "5"
                    }
                },
                {
                    "@type": "WebPage",
                    "name": "Smart Pixel Analytics - Alternative Google Analytics",
                    "description": "Solution analytics française pour remplacer Google Analytics",
                    "publisher": {
                        "@type": "Organization",
                        "name": "Smart Pixel"
                    }
                }
            ]
        }
    </script>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <style>
        /* ====== VARIABLES PROFESSIONNELLES ====== */
        :root {
            --primary: #9d86ff;
            /* Bleu professionnel */
            --primary-dark: #917ded;
            --primary-light: #af9dfe;
            --secondary: #988dc6;
            /* Gris texte */
            --accent: #4ecdc4;
            /* Vert succès */
            --accent-dark: #059669;
            --warning: #f59e0b;
            --danger: #ff6b8b;
            --light: #f8fafc;
            --dark: #0f172a;
            --gray-light: #f1f5f9;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 40px -15px rgba(0, 0, 0, 0.15);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --transition: all 0.3s ease;
            --container: 1200px;
        }

        /* ====== RESET & BASE ====== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: white;
            overflow-x: hidden;
        }

        .container {
            max-width: var(--container);
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ====== TYPOGRAPHIE ====== */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-weight: 700;
            line-height: 1.2;
            color: var(--dark);
        }

        h1 {
            font-size: 3.5rem;
            letter-spacing: -0.02em;
        }

        h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        p {
            margin-bottom: 1.5rem;
            color: var(--secondary);
            font-size: 1.125rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        /* ====== BOUTONS ====== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border-color: var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .btn-accent {
            background: var(--accent);
            color: white;
        }

        .btn-accent:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }

        /* ====== HEADER PROFESSIONNEL ====== */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            z-index: 1000;
            padding: 16px 0;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--dark);
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark);
        }

        /* ====== HERO SECTION OPTIMISÉE ====== */
        .hero {
            padding: 160px 0 100px;
            background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 2rem;
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .hero-title {
            margin-bottom: 1.5rem;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 3rem;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 4rem;
            flex-wrap: wrap;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-top: 4rem;
            padding-top: 3rem;
            border-top: 1px solid var(--border-color);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* ====== SECTION CLIENTS ====== */
        .clients-section {
            padding: 80px 0;
            background: white;
        }

        .clients-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3rem;
            margin-top: 3rem;
        }

        .client-card {
            text-align: center;
            padding: 2rem;
            background: var(--light);
            border-radius: var(--radius-lg);
            transition: var(--transition);
        }

        .client-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .client-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0 auto 1.5rem;
        }

        /* ====== SECTION PROBLÈME/SOLUTION ====== */
        .problem-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .problem-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .problem-column,
        .solution-column {
            padding: 3rem;
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
        }

        .problem-item,
        .solution-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .problem-item:last-child,
        .solution-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .problem-icon {
            color: var(--danger);
            font-size: 1.5rem;
        }

        .solution-icon {
            color: var(--accent);
            font-size: 1.5rem;
        }

        /* ====== SECTION FONCTIONNALITÉS ====== */
        .features-section {
            padding: 100px 0;
            background: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            padding: 2.5rem;
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .feature-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
        }

        /* ====== SECTION INTÉGRATION ====== */
        .integration-section {
            padding: 100px 0;
            background: var(--light);
        }

        .integration-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin: 3rem 0;
        }

        .step {
            text-align: center;
            padding: 2.5rem 2rem;
            background: white;
            border-radius: var(--radius-lg);
            position: relative;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 1.5rem;
            font-size: 1.2rem;
        }

        .code-snippet {
            background: var(--dark);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin: 3rem 0;
            color: white;
            font-family: 'Courier New', monospace;
            position: relative;
            overflow: hidden;
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            color: var(--gray-light);
        }

        .copy-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
        }

        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* ====== SECTION TARIFS ====== */
        .pricing-section {
            padding: 100px 0;
            background: white;
        }

        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 3rem;
        }

        .pricing-card {
            padding: 3rem;
            background: white;
            border-radius: var(--radius-xl);
            border: 2px solid var(--border-color);
            transition: var(--transition);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .pricing-card:hover {
            border-color: var(--primary);
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }

        .pricing-card.featured {
            border-color: var(--primary);
            background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
        }

        .featured-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 8px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .price-tag {
            font-size: 3rem;
            font-weight: 800;
            color: var(--dark);
            margin: 1.5rem 0;
        }

        .price-tag span {
            font-size: 1rem;
            color: var(--secondary);
            font-weight: 500;
        }

        .pricing-features {
            list-style: none;
            margin: 2rem 0;
            flex-grow: 1;
        }

        .pricing-features li {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--secondary);
        }

        .feature-check {
            color: var(--accent);
        }

        .limited-offer {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1px solid #fbbf24;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: center;
        }

        /* ====== FOOTER ====== */
        .footer {
            background: var(--dark);
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
            text-decoration: none;
            margin-bottom: 1.5rem;
        }

        .footer-description {
            color: #94a3b8;
            margin-bottom: 1.5rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            color: #94a3b8;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .social-links a:hover {
            color: white;
        }

        .footer-links h4 {
            color: white;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        /* ====== RESPONSIVE ====== */
        @media (max-width: 1024px) {

            .hero-stats,
            .clients-grid,
            .features-grid,
            .integration-steps,
            .pricing-cards,
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .problem-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            h1 {
                font-size: 2.8rem;
            }

            h2 {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: fixed;
                top: 80px;
                left: 0;
                width: 100%;
                background: white;
                padding: 2rem;
                box-shadow: var(--shadow-lg);
                flex-direction: column;
                text-align: center;
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero-stats,
            .clients-grid,
            .features-grid,
            .integration-steps,
            .pricing-cards,
            .footer-grid {
                grid-template-columns: 1fr;
            }

            .hero-cta {
                flex-direction: column;
            }

            .hero {
                padding: 140px 0 80px;
            }

            h1 {
                font-size: 2.4rem;
            }

            h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 16px;
            }

            h1 {
                font-size: 2rem;
            }

            h2 {
                font-size: 1.8rem;
            }

            .feature-card,
            .step,
            .pricing-card {
                padding: 2rem 1.5rem;
            }

            .footer-bottom {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        /* ====== ANIMATIONS ====== */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate {
            animation: fadeIn 0.6s ease forwards;
        }
    </style>
</head>

<body itemscope itemtype="https://schema.org/WebPage">
    <!-- === HEADER === -->
    <header class="header" role="banner">
        <div class="container">
            <nav class="nav" role="navigation" aria-label="Navigation principale">
                <a href="/" class="logo" itemprop="url">
                    <div class="logo-icon" aria-hidden="true">
                    </div>
                    <span itemprop="name">Alternative Analytics</span>
                </a>

                <div class="nav-links" id="navLinks">
                    <a href="#solution" itemprop="url">Solution</a>
                    <a href="#fonctionnalites" itemprop="url">Fonctionnalités</a>
                    <a href="#integration" itemprop="url">Intégration</a>
                    <a href="#tarifs" itemprop="url">Tarifs</a>
                    <a href="./smart_pixel_v2/public/login.php" class="btn btn-secondary">Connexion</a>
                </div>

                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu mobile" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- === HERO SECTION === -->
    <section class="hero" role="region" aria-labelledby="hero-title">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge" role="note">
                    <span>Pas besoin des GAFAM !</span>
                </div>

                <h1 id="hero-title" class="hero-title">
                    Reprends le <span style="color: var(--danger);">control</span> <br>
                    <span style="color: var(--primary);">de tes données</span>
                </h1>

                <p class="hero-subtitle">
                    <strong>Pourquoi un service analytique souverain ?</strong><br>
                    Pour stocker ses données et <u>être réelement le seul à pouvoir les exploiter</u>.<br>
                    <!--<em>Notre promesse : Vos données restent en France, pas chez Google et aucune donnée n'est vendue à un tiers.</em>-->
                </p>

                <div class="hero-cta">
                    <a href="./smart_pixel_v2/public/index.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 20px 40px;">
                        <i class="fas fa-bolt"></i>
                        <strong>CRÉER MON PREMIER DASHBOARD</strong><br>
                        <!--<small style="font-size: 0.8rem; opacity: 0.9;">Aucune CB requise</small>-->
                    </a>
                    <a href="#solution" class="btn btn-secondary" style="padding: 20px 40px;">
                        <i class="fas fa-play-circle"></i>
                        Voir comment ça marche
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">RGPD Garanti</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">2min</div>
                        <div class="stat-label">Installation</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">57</div>
                        <div class="stat-label">Sites Français</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">0€/mois</div>
                        <div class="stat-label">À partir de</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- === CLIENTS SECTION === -->
    <section class="clients-section" role="region" aria-labelledby="clients-title">
        <div class="container">
            <div class="section-title">
                <h2 id="clients-title">Smart Pixel est fait pour vous si :</h2>
                <p class="section-subtitle" style="max-width: 800px; margin: 0 auto;">
                    Découvrez pourquoi nos utilisateurs ont choisi l'alternative française à Google Analytics
                </p>
            </div>

            <div class="clients-grid">
                <div class="client-card animate">
                    <div class="client-avatar"><i class="fa-solid fa-code"></i></div>
                    <h3>Développeur Freelance</h3>
                    <p>"J'en avais marre de configurer Google Tag Manager pour chaque client."</p>
                </div>

                <div class="client-card animate">
                    <div class="client-avatar"><i class="fa-solid fa-cubes"></i></div>
                    <h3>Petit Commerçant</h3>
                    <p>"Je veux juste savoir combien de visiteurs viennent sur mon site."</p>
                </div>

                <div class="client-card animate">
                    <div class="client-avatar"><i class="fa-regular fa-keyboard"></i></div>
                    <h3>Blogueur</h3>
                    <p>"GA4 est trop complexe, je voulais des stats simples."</p>
                </div>

                <div class="client-card animate">
                    <div class="client-avatar"><i class="fa-solid fa-hexagon-nodes"></i></div>
                    <h3>Entreprise Française</h3>
                    <p>"Nos données doivent rester en France pour la conformité et la souveraineté numérique."</p>
                </div>
            </div>
        </div>
    </section>

    <!-- === PROBLEM/SOLUTION SECTION === -->
    <section id="solution" class="problem-section" role="region" aria-labelledby="problem-title">
        <div class="container">
            <div class="section-title">
                <h2 id="problem-title">Le problème avec Google Analytics</h2>
                <p class="section-subtitle">Et comment Smart Pixel le résout</p>
            </div>

            <div class="problem-grid">
                <div class="problem-column animate">
                    <h3 style="color: var(--danger); margin-bottom: 2rem;">
                        <i class="fas fa-times-circle"></i> Google Analytics
                    </h3>

                    <div class="problem-item">
                        <div class="problem-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h4>Complexité extrême</h4>
                            <p>Interface surchargée pour le besoin réel de 80% des utilisateurs</p>
                        </div>
                    </div>

                    <div class="problem-item">
                        <div class="problem-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h4>Problèmes RGPD</h4>
                            <p>Données aux USA, conformité difficile, aucun contrôle sur les données</p>
                        </div>
                    </div>

                    <div class="problem-item">
                        <div class="problem-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div>
                            <h4>Impact performances</h4>
                            <p>Script lourd qui ralentit votre site</p>
                        </div>
                    </div>
                </div>

                <div class="solution-column animate">
                    <h3 style="color: var(--accent); margin-bottom: 2rem;">
                        <i class="fas fa-check-circle"></i> Smart Pixel
                    </h3>

                    <div class="solution-item">
                        <div class="solution-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <h4>Simplicité extrême</h4>
                            <p>Dashboard clair, installation 2 minutes</p>
                        </div>
                    </div>

                    <div class="solution-item">
                        <div class="solution-icon">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div>
                            <h4>RGPD par défaut</h4>
                            <p>Données en France, conformité garantie</p>
                        </div>
                    </div>

                    <div class="solution-item">
                        <div class="solution-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div>
                            <h4>Performance optimale</h4>
                            <p>Script léger, 0 impact sur votre site</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- === CARTE MONDIALE === -->
    <section class="world-map-section">
        <div class="container">
            <div class="section-title">
                <h2>Déjà utilisé dans le monde entier</h2>
                <p class="section-subtitle">
                    <span id="total-sites">57</span> sites nous font confiance ·
                    <span id="total-hits">28k</span> analyses ce mois
                </p>
            </div>

            <!-- Carte -->
            <div id="public-map" style="width: 100%; height: 400px; margin: 30px 0;"></div>

            <!-- Top pays -->
            <div class="top-countries" id="top-countries"></div>
        </div>
    </section>

    <style>
        .world-map-section {
            padding: 60px 0;
            background: linear-gradient(180deg, #0f172a 0%, #0a0a12 100%);
        }

        .top-countries {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .country-badge {
            background: rgba(171, 159, 242, 0.1);
            border: 1px solid #313244;
            border-radius: 30px;
            padding: 8px 20px;
            font-size: 14px;
            color: #cdd6f4;
        }

        .country-badge strong {
            color: #ab9ff2;
            margin-right: 5px;
        }
    </style>

    <!-- AmCharts -->
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

    <script>
        // === CARTE MONDIALE - CHARGEMENT DIRECT ===
        (function() {
            console.log("Chargement de la carte...");

            // Attendre que le DOM soit prêt
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initMap);
            } else {
                initMap();
            }

            function initMap() {
                console.log("Initialisation de la carte");

                // Données de démo qui MARCHENT À COUP SÛR
                const demoData = [{
                        country: 'France',
                        hit_count: 12500
                    },
                    {
                        country: 'USA',
                        hit_count: 8300
                    },
                    {
                        country: 'Canada',
                        hit_count: 4200
                    },
                    {
                        country: 'UK',
                        hit_count: 3800
                    },
                    {
                        country: 'Allemagne',
                        hit_count: 3100
                    },
                    {
                        country: 'Italie',
                        hit_count: 2900
                    },
                    {
                        country: 'Espagne',
                        hit_count: 2700
                    },
                    {
                        country: 'Belgique',
                        hit_count: 2100
                    },
                    {
                        country: 'Suisse',
                        hit_count: 1800
                    },
                    {
                        country: 'Pays-Bas',
                        hit_count: 1600
                    }
                ];

                // Afficher les stats
                document.getElementById('total-sites').textContent = '57';
                document.getElementById('total-hits').textContent = '28k';

                renderMap(demoData);

                // Essayer de charger les vraies données (optionnel)
                fetch('public_stats.php')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.countries.length > 0) {
                            renderMap(data.countries);
                            document.getElementById('total-sites').textContent = data.total_sites;
                            document.getElementById('total-hits').textContent = (data.recent_hits / 1000).toFixed(1) + 'k';
                        }
                    })
                    .catch(err => console.log("Pas de données serveur, utilisation du démo"));
            }

            function renderMap(data) {
                console.log("Rendu de la carte avec", data.length, "points");

                // Nettoyer l'ancienne carte
                if (window.mapRoot) {
                    window.mapRoot.dispose();
                }

                // Créer la racine
                window.mapRoot = am5.Root.new("public-map");
                window.mapRoot.setThemes([am5themes_Animated.new(window.mapRoot)]);

                // Créer la carte
                const chart = window.mapRoot.container.children.push(
                    am5map.MapChart.new(window.mapRoot, {
                        projection: am5map.geoMercator(),
                        panX: "rotateX",
                        panY: "translateY",
                        wheelable: true,
                        zoomLevel: 1,
                        maxZoomLevel: 4
                    })
                );

                // Fond de carte
                const polygonSeries = chart.series.push(
                    am5map.MapPolygonSeries.new(window.mapRoot, {
                        geoJSON: am5geodata_worldLow,
                        fill: am5.color(0x2d3748),
                        stroke: am5.color(0x4a5568),
                        strokeWidth: 0.5
                    })
                );

                // Série de points
                const pointSeries = chart.series.push(
                    am5map.MapPointSeries.new(window.mapRoot, {})
                );

                // Style des points avec glow
                pointSeries.bullets.push(function(root, series, dataItem) {
                    return am5.Bullet.new(root, {
                        sprite: am5.Circle.new(root, {
                            radius: dataItem.dataContext.size || 12,
                            fill: am5.color(0xff6b8b),
                            stroke: am5.color(0xffffff),
                            strokeWidth: 2,
                            shadowColor: am5.color(0xff6b8b),
                            shadowBlur: 15,
                            shadowOffsetX: 0,
                            shadowOffsetY: 0,
                            shadowOpacity: 0.8,
                            tooltipText: "{country}\n{visites} visites"
                        })
                    });
                });

                // Coordonnées des capitales
                const coords = {
                    'France': [2.3522, 48.8566],
                    'USA': [-77.0369, 38.9072],
                    'Canada': [-75.6972, 45.4215],
                    'UK': [-0.1278, 51.5074],
                    'Allemagne': [13.4050, 52.5200],
                    'Italie': [12.4964, 41.9028],
                    'Espagne': [-3.7038, 40.4168],
                    'Belgique': [4.3517, 50.8503],
                    'Suisse': [7.4474, 46.9480],
                    'Pays-Bas': [4.9041, 52.3676],
                    'Inde': [77.1025, 28.7041],
                    'Chine': [116.4074, 39.9042],
                    'Japon': [139.6917, 35.6895],
                    'Australie': [149.1300, -35.2809],
                    'Brésil': [-47.9292, -15.7801]
                };

                // Préparer les points
                const points = [];
                const maxHits = Math.max(...data.map(d => d.hit_count));

                data.forEach(item => {
                    let countryKey = item.country;
                    // Mapping des noms
                    if (item.country === 'USA' || item.country === 'United States') countryKey = 'USA';
                    if (item.country === 'UK' || item.country === 'United Kingdom') countryKey = 'UK';

                    if (coords[countryKey]) {
                        const size = 8 + (item.hit_count / maxHits) * 22;

                        points.push({
                            geometry: {
                                type: "Point",
                                coordinates: coords[countryKey]
                            },
                            country: item.country,
                            visites: item.hit_count.toLocaleString(),
                            size: size,
                            value: item.hit_count
                        });
                    }
                });

                // Si pas de points, utiliser les données de démo avec coordonnées
                if (points.length === 0) {
                    points.push({
                        geometry: {
                            type: "Point",
                            coordinates: coords['France']
                        },
                        country: "France",
                        visites: "12500",
                        size: 20,
                        value: 12500
                    }, {
                        geometry: {
                            type: "Point",
                            coordinates: coords['USA']
                        },
                        country: "USA",
                        visites: "8300",
                        size: 18,
                        value: 8300
                    }, {
                        geometry: {
                            type: "Point",
                            coordinates: coords['Canada']
                        },
                        country: "Canada",
                        visites: "4200",
                        size: 14,
                        value: 4200
                    }, {
                        geometry: {
                            type: "Point",
                            coordinates: coords['UK']
                        },
                        country: "UK",
                        visites: "3800",
                        size: 13,
                        value: 3800
                    }, {
                        geometry: {
                            type: "Point",
                            coordinates: coords['Allemagne']
                        },
                        country: "Allemagne",
                        visites: "3100",
                        size: 12,
                        value: 3100
                    });
                }

                // Ajouter les points
                pointSeries.data.setAll(points);

                // Centrer la carte
                chart.set("zoomToGeoPoint", {
                    longitude: 0,
                    latitude: 20
                }, 1);

                console.log("Carte chargée avec", points.length, "points");
            }
        })();

        function updateUI(data) {
            document.getElementById('total-sites').textContent = data.total_sites;
            document.getElementById('total-hits').textContent = (data.recent_hits / 1000).toFixed(1) + 'k';
        }
    </script>

    <!-- === INTEGRATION SECTION === -->
    <section id="integration" class="integration-section" role="region" aria-labelledby="integration-title">
        <div class="container">
            <div class="section-title">
                <h2 id="integration-title">Intégration en 2 minutes</h2>
                <p class="section-subtitle">Installer votre suivie Analytics en une seule ligne de code</p>
            </div>

            <div class="integration-steps">
                <div class="step animate">
                    <div class="step-number">1</div>
                    <h3>Créez votre compte</h3>
                    <p>Inscription gratuite en 5 secondes, aucun paiement requis</p>
                </div>

                <div class="step animate">
                    <div class="step-number">2</div>
                    <h3>Ajoutez votre site</h3>
                    <p>Donnez un nom à votre site et récupérez votre ID de tracking</p>
                </div>

                <div class="step animate">
                    <div class="step-number">3</div>
                    <h3>Installez le script</h3>
                    <p>Copiez-collez une ligne de code dans le &lt;head&gt; de votre site</p>
                </div>
            </div>

            <div class="code-snippet animate">
                <div class="code-header">
                    <span>Code d'intégration Smart Pixel</span>
                    <button class="copy-btn" onclick="copyCode()" aria-label="Copier le code">
                        <i class="fas fa-copy"></i> Copier
                    </button>
                </div>
                <pre><code style="color: #e2e8f0;">&lt;!-- Smart Pixel Analytics --&gt;
&lt;script data-sp-id="VOTRE_ID_ICI" 
        src="https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/tracker.js" 
        async&gt;
&lt;/script&gt;</code></pre>
            </div>

            <div style="text-align: center;">
                <a href="./smart_pixel_v2/public/index.php" class="btn btn-primary" style="padding: 20px 50px; font-size: 1.1rem;">
                    <i class="fas fa-rocket"></i>
                    Créer des maintenant
                </a>
            </div>
        </div>
    </section>

    <!-- === PRICING SECTION === -->
    <section id="tarifs" class="pricing-section" role="region" aria-labelledby="pricing-title">
        <div class="container">
            <div class="section-title">
                <h2 id="pricing-title">Tarifs transparents</h2>
                <p class="section-subtitle">Payez pour l'hébergement et le support, pas pour vos données</p>
            </div>

            <div class="pricing-cards">
                <!-- Plan Gratuit -->
                <div class="pricing-card animate">
                    <h3>Gratuit</h3>
                    <div class="price-tag">0€<span>/mois</span></div>
                    <p>Pour découvrir et tester</p>

                    <ul class="pricing-features">
                        <li><i class="fas fa-check feature-check"></i> 1 site web</li>
                        <li><i class="fas fa-check feature-check"></i> 1000 vues/mois</li>
                        <li><i class="fas fa-check feature-check"></i> Dashboard complet</li>
                        <li><i class="fas fa-check feature-check"></i> 365 jours de rétention</li>
                        <li><i class="fas fa-check feature-check"></i> Support communautaire</li>
                    </ul>

                    <a href="./smart_pixel_v2/public/index.php" class="btn btn-secondary" style="margin-top: auto;">
                        Commencer gratuitement
                    </a>
                </div>

                <!-- Plan Pro -->
                <div class="pricing-card featured animate">
                    <div class="featured-badge">Recommandé</div>
                    <h3>Pro</h3>
                    <div class="price-tag">9€<span>/mois</span></div>
                    <p>Pour les sites professionnels</p>

                    <ul class="pricing-features">
                        <li><i class="fas fa-check feature-check"></i> <strong>10 sites web</strong></li>
                        <li><i class="fas fa-check feature-check"></i> 100 000 vues/mois</li>
                        <li><i class="fas fa-check feature-check"></i> Dashboard complet</li>
                        <li><i class="fas fa-check feature-check"></i> 365 jours de rétention</li>
                        <li><i class="fas fa-check feature-check"></i> Rapport automatique</li>
                        <li><i class="fas fa-check feature-check"></i> API d'accès</li>
                        <li><i class="fas fa-check feature-check"></i> Support prioritaire</li>
                    </ul>

                    <a href="./smart_pixel_v2/public/index.php?plan=pro" class="btn btn-primary" style="margin-top: auto;">
                        <i class="fas fa-gem"></i>
                        Devenir Pro
                    </a>

                    <!--<div class="limited-offer">
                        <i class="fas fa-gift" style="color: var(--warning);"></i>
                        <strong>Offre MVP :</strong> Prix garanti à vie
                    </div>-->
                </div>

                <!-- Plan Business --
                <div class="pricing-card animate">
                    <h3>Business</h3>
                    <div class="price-tag">29€<span>/mois</span></div>
                    <p>Pour les entreprises et agences</p>
                    
                    <ul class="pricing-features">
                        <li><i class="fas fa-check feature-check"></i> <strong>50 sites web</strong></li>
                        <li><i class="fas fa-check feature-check"></i> Vues illimitées</li>
                        <li><i class="fas fa-check feature-check"></i> Toutes features Pro</li>
                        <li><i class="fas fa-check feature-check"></i> 2 ans de rétention</li>
                        <li><i class="fas fa-check feature-check"></i> Accès multi-utilisateurs</li>
                        <li><i class="fas fa-check feature-check"></i> Support téléphone</li>
                        <li><i class="fas fa-check feature-check"></i> Intégrations custom</li>
                    </ul>
                    
                    <a href="contact@gael-berru.com" class="btn btn-secondary" style="margin-top: auto;">
                        <i class="fas fa-phone-alt"></i>
                        Nous contacter
                    </a>
                </div>-->
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <p style="color: var(--secondary);">
                    <i class="fas fa-sync-alt"></i> Satisfait ou remboursé
                    <i class="fas fa-ban"></i> Pas de carte bancaire requise pour commencer
                </p>
            </div>
        </div>
    </section>

    <!-- === FOOTER === -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="#" class="footer-logo">
                        <div class="logo-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        Smart Pixel
                    </a>
                    <p class="footer-description">
                        Alternative open-source et souveraine à Google Analytics.<br>
                        Code auditable, données protégées, analytics éthique, aucune données vendue à quiconque.
                    </p>
                    <div class="social-links">
                        <a href="https://github.com/berru-g/smart_pixel_v2" aria-label="GitHub">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="#" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" aria-label="LinkedIn">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>

                <div class="footer-links">
                    <h4>Produit</h4>
                    <ul>
                        <li><a href="#fonctionnalites">Fonctionnalités</a></li>
                        <li><a href="#solution">Solution</a></li>
                        <li><a href="#tarifs">Tarifs</a></li>
                        <li><a href="./doc/">Documentation</a></li>
                        <li><a href="https://github.com/berru-g/smart_pixel_v2/blob/main/public/pixel.php">API</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4>Entreprise</h4>
                    <ul>
                        <li><a href="https://gael-berru.com">À propos</a></li>
                        <li><a href="https://gael-berru.com">Blog</a></li>
                        <li><a href="https://gael-berru.com">Contact</a></li>
                        <li><a href="https://gael-berru.com">Presse</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4>Légal</h4>
                    <ul>
                        <li><a href="#">Mentions légales</a></li>
                        <li><a href="#">Confidentialité</a></li>
                        <li><a href="#">RGPD</a></li>
                        <li><a href="#">CGU</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>
                    © 2025 Smart Pixel Analytics. Développé avec <i class="fas fa-heart" style="color: var(--danger);"></i>
                    en France par <a href="https://gael-berru.com" style="color: #94a3b8;">Berru-g</a>.
                </p>
                <p>
                    <i class="fas fa-map-marker-alt"></i> Hébergé en France ·
                    <i class="fas fa-leaf"></i> Éco-responsable
                </p>
            </div>
        </div>
    </footer>
    <script src="./RGPD/cookie.js"></script>
    <!-- === SCRIPT === -->
    <script>
        // Mobile Menu
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuBtn.addEventListener('click', () => {
            const isExpanded = mobileMenuBtn.getAttribute('aria-expanded') === 'true';
            mobileMenuBtn.setAttribute('aria-expanded', !isExpanded);
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = isExpanded ?
                '<i class="fas fa-bars"></i>' :
                '<i class="fas fa-times"></i>';
        });

        // Copy Code
        function copyCode() {
            const code = `<script data-sp-id="VOTRE_ID_ICI" src="https://gael-berru.com/smart_phpixel/tracker.js" async><\/script>`;
            navigator.clipboard.writeText(code).then(() => {
                const btn = document.querySelector('.copy-btn');
                btn.innerHTML = '<i class="fas fa-check"></i> Copié !';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-copy"></i> Copier';
                }, 2000);
            });
        }

        // Scroll Animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, observerOptions);

        // Observe all animate elements
        document.querySelectorAll('.animate').forEach(el => {
            observer.observe(el);
        });

        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });

                    // Close mobile menu if open
                    if (navLinks.classList.contains('active')) {
                        navLinks.classList.remove('active');
                        mobileMenuBtn.setAttribute('aria-expanded', 'false');
                        mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                    }
                }
            });
        });
    </script>

    <!-- === COOKIE BANNER (optionnel) === -->
    <div id="cookie-banner" style="display: none;">
        <div class="cookie-container">
            <div class="cookie-header">
                <div class="cookie-icon">🛡️</div>
                <div class="cookie-title-wrapper">
                    <h3 class="cookie-title">Transparence totale sur vos données</h3>
                    <p class="cookie-subtitle">Respect RGPD • Open source</p>
                </div>
            </div>

            <div class="cookie-content">
                <p class="cookie-description">
                    <strong>Ici, aucun de vos clics n'est vendu à Google ou Facebook.</strong><br>
                    J'utilise <strong>Smart Pixel</strong>, mon propre système d'analyse développé avec éthique, dans le respect
                    des lois RGPD.
                </p>
                <p class="cookie-description">
                    En autorisant l'analyse, vous m'aidez à améliorer ce site <strong>sans enrichir les GAFAM de vos
                        données</strong>.
                </p>
            </div>

            <div class="cookie-buttons">
                <button class="cookie-btn accept-necessary" onclick="acceptCookies('necessary')">
                    Non merci
                </button>
                <button class="cookie-btn accept-all" onclick="acceptCookies('all')">
                    Ok pour moi
                </button>
            </div>

            <div class="cookie-footer">
                <a href="https://github.com/berru-g/smart_phpixel" target="_blank" class="cookie-link">
                    Voir le code source de Smart Pixel
                </a>
            </div>
        </div>
    </div>
</body>

</html>