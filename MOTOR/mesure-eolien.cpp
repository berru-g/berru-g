#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// OLED
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET    -1
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// Pins
const int voltagePin = 35;  // GPIO35 (tension)
const int currentPin = 34;  // GPIO34 (courant)

// Constantes
const float voltageDividerRatio = 0.5;  // R1=R2=10kΩ → ratio=0.5
const float shuntResistance = 0.1;      // Shunt 0.1Ω
const float ampliGain = 100.0;           // Gain LM358 (R4=1kΩ)
const float vRef = 3.3;                  // Tension de référence ESP32

void setup() {
  Serial.begin(115200);
  Wire.begin();
  display.begin(SSD1306_SWITCHCAPVCC, 0x3C);
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.println("Initialisation...");
  display.display();
  delay(1000);
}

void loop() {
  // Mesure de tension
  int voltageRaw = analogRead(voltagePin);
  float voltage = (voltageRaw / 4095.0) * vRef / voltageDividerRatio;

  // Mesure de courant
  int currentRaw = analogRead(currentPin);
  float currentVoltage = (currentRaw / 4095.0) * vRef;
  float current = currentVoltage / (shuntResistance * ampliGain);

  // Affiche sur OLED
  display.clearDisplay();
  display.setCursor(0, 0);
  display.print("V: ");
  display.print(voltage, 2);
  display.println("V");
  display.print("A: ");
  display.print(current, 3);
  display.println("A");
  display.display();

  // Envoie par USB (pour debug)
  Serial.print("Tension: ");
  Serial.print(voltage, 2);
  Serial.print("V, Courant: ");
  Serial.print(current, 3);
  Serial.println("A");

  delay(1000);
}