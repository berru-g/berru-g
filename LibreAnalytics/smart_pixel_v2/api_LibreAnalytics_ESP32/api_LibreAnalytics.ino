// Affiche les statistiques de ton compte LibreAnalytics sur un écran OLED et clignote une LED ( a changer pour un truc plus...pertinent) si de nouveaux visiteurs sont détectés.
// Carte esp32 oled integré 
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <U8g2lib.h>
#include <time.h>

// OLED
U8G2_SSD1306_128X64_NONAME_F_HW_I2C u8g2(U8G2_R0);

// Wi-Fi
const char* ssid = "ta box";
const char* password = "ton-mdp";

// API
String apiKey = "ton_api_key_LibreAnalytics";
String siteId = "SP_ton_site_id_LibreAnalytics";
String startDate = "2026-01-01"; // Date de début pour les statistiques (format YYYY-MM-DD)

// Variables pour suivre les visiteurs
int lastVisits = 0;
int lastUniqueVisitors = 0;

// LED intégrée
const int ledPin = 2;

void setup() {
  Serial.begin(115200);
  u8g2.begin();
  pinMode(ledPin, OUTPUT);
  digitalWrite(ledPin, LOW);

  // Animation de démarrage
  startupAnimation();

  // Test de l'OLED
  testOLED();

  // Connexion Wi-Fi
  testWifi();

  // Message de démarrage
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_6x10_tr);
  u8g2.drawStr(0, 10, "LibreAnalytics API");
  u8g2.drawStr(0, 25, "gael-berru.com");
  u8g2.drawStr(0, 40, "Demarrage OK!");
  u8g2.sendBuffer();
  delay(2000);
}

void loop() {
  // Test de l'API
  testAPI();
  delay(600000); // 10 minutes
}

// Animation de démarrage (barre de progression)
void startupAnimation() {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_6x10_tr);
  u8g2.drawStr(0, 10, "LibreAnalytics API");
  u8g2.sendBuffer();

  for (int i = 0; i <= 100; i += 5) {
    u8g2.clearBuffer();
    u8g2.drawStr(0, 10, "LibreAnalytics API");
    u8g2.drawStr(0, 25, "by berru-g");

    // Barre de progression
    u8g2.drawFrame(0, 40, 128, 10);
    u8g2.drawBox(0, 40, map(i, 0, 100, 0, 128), 10);

    char progressStr[10];
    snprintf(progressStr, 10, "%d%%", i);
    u8g2.drawStr(50, 55, progressStr);
    u8g2.sendBuffer();
    delay(100);
  }
}

// Test de l'OLED
void testOLED() {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_6x10_tr);
  u8g2.drawStr(0, 10, "Open source");
  u8g2.drawStr(0, 25, "github.com/berru-g");
  u8g2.sendBuffer();
  Serial.println("OLED : OK");
  delay(1000);
}

// Test de la connexion Wi-Fi
void testWifi() {
  u8g2.clearBuffer();
  u8g2.drawStr(0, 10, "Test Wi-Fi...");
  u8g2.drawStr(0, 25, "Connexion a:");
  u8g2.drawStr(0, 40, ssid);
  u8g2.sendBuffer();

  WiFi.begin(ssid, password);
  Serial.print("Connexion Wi-Fi...");
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;

    // Affiche le nombre de tentatives
    if (attempts % 5 == 0) {
      char attemptStr[30];
      snprintf(attemptStr, 30, "Tentative %d/20", attempts);
      u8g2.clearBuffer();
      u8g2.drawStr(0, 10, "Test Wi-Fi...");
      u8g2.drawStr(0, 25, attemptStr);
      u8g2.sendBuffer();
    }
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWi-Fi : OK");
    Serial.print("IP : ");
    Serial.println(WiFi.localIP());
    u8g2.clearBuffer();
    u8g2.drawStr(0, 10, "Wi-Fi: OK");
    u8g2.drawStr(0, 25, "IP:");
    u8g2.drawStr(0, 40, WiFi.localIP().toString().c_str());
    u8g2.sendBuffer();
  } else {
    Serial.println("\nWi-Fi : ECHEC");
    u8g2.clearBuffer();
    u8g2.drawStr(0, 10, "Wi-Fi: ECHEC");
    u8g2.drawStr(0, 25, "Verifie SSID/MDP");
    u8g2.sendBuffer();
    //while (true); // Bloque si Wi-Fi échoue (à enlever en production)
  }
  delay(2000);
}

// Test de l'API
void testAPI() {
  // Récupère la date du jour
  struct tm timeinfo;
  if (!getLocalTime(&timeinfo)) {
    Serial.println("Erreur: Date non disponible");
    u8g2.clearBuffer();
    u8g2.drawStr(0, 10, "Erreur: Date");
    u8g2.drawStr(0, 25, "non disponible");
    u8g2.sendBuffer();
    return;
  }
  char endDate[11];
  strftime(endDate, sizeof(endDate), "%Y-%m-%d", &timeinfo);
  Serial.printf("Date du jour : %s\n", endDate);

  // Construit l'URL
  String url = "https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?site_id=" + siteId +
               "&start_date=" + startDate + "&end_date=" + endDate + "&api_key=" + apiKey;
  Serial.println("URL : " + url);

  // Affiche "Appel API..." sur l'OLED
  u8g2.clearBuffer();
  u8g2.drawStr(0, 10, "Appel API...");
  u8g2.drawStr(0, 25, endDate);
  u8g2.sendBuffer();

  // Appel HTTP
  HTTPClient http;
  http.begin(url);
  int httpCode = http.GET();

  if (httpCode > 0) {
    String payload = http.getString();
    Serial.println("Réponse API : " + payload);

    // Parse le JSON
    DynamicJsonDocument doc(2048);
    DeserializationError error = deserializeJson(doc, payload);

    if (error) {
      Serial.print("Erreur JSON : ");
      Serial.println(error.c_str());
      u8g2.clearBuffer();
      u8g2.drawStr(0, 10, "Erreur JSON");
      u8g2.drawStr(0, 25, error.c_str());
      u8g2.sendBuffer();
    } else {
      // Extrait les valeurs
      int visits = doc["visits"];
      int uniqueVisitors = doc["unique_visitors"];

      Serial.printf("Visites : %d, Uniques : %d\n", visits, uniqueVisitors);

      // Affiche les résultats
      u8g2.clearBuffer();
      u8g2.setFont(u8g2_font_6x10_tr);
      u8g2.drawStr(0, 10, "Statistiques:");

      char visitsStr[20], uniqueStr[20];
      snprintf(visitsStr, 20, "Visites: %d", visits);
      snprintf(uniqueStr, 20, "Uniques: %d", uniqueVisitors);
      u8g2.drawStr(0, 25, visitsStr);
      u8g2.drawStr(0, 40, uniqueStr);

      // Vérifie les nouveaux visiteurs
      if (visits > lastVisits || uniqueVisitors > lastUniqueVisitors) {
        u8g2.drawStr(0, 55, "NOUVEAUX VISITEURS!");
        u8g2.sendBuffer();

        // Clignote la LED
        for (int i = 0; i < 5; i++) {
          digitalWrite(ledPin, HIGH);
          delay(500);
          digitalWrite(ledPin, LOW);
          delay(500);
        }
        lastVisits = visits;
        lastUniqueVisitors = uniqueVisitors;
      } else {
        u8g2.drawStr(0, 55, "Aucun nouveau.");
        u8g2.sendBuffer();
      }
    }
  } else {
    Serial.printf("Erreur HTTP : %s\n", http.errorToString(httpCode).c_str());
    u8g2.clearBuffer();
    u8g2.drawStr(0, 10, "Erreur HTTP");
    u8g2.drawStr(0, 25, http.errorToString(httpCode).c_str());
    u8g2.sendBuffer();
  }
  http.end();
}