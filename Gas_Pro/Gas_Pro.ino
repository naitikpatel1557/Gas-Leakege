#include <WiFi.h>
#include <Wire.h>               // Required for I2C communication
#include <LiquidCrystal_I2C.h>  // Library for the LCD
#include "ThingSpeak.h"

// --- LCD CONFIGURATION ---
// set the LCD number of columns and rows
int lcdColumns = 16;
int lcdRows = 2;

// set LCD address, number of columns and rows
// Common addresses are 0x27 or 0x3F. If 0x27 doesn't work, try 0x3F.
LiquidCrystal_I2C lcd(0x27, lcdColumns, lcdRows);  

// --- NETWORK CONFIGURATION ---
char ssid[] = "AkikPatel1557";   
char pass[] = "00000000";   
int keyIndex = 0;            
WiFiClient  client;

// --- PIN DEFINITIONS ---
#define lightPin 2
#define buzzerPin 4
#define gasPin 34

// --- THINGSPEAK CONFIGURATION ---
unsigned long myChannelNumber = 3176303;
const char * myWriteAPIKey = "H9N6XU6VR5SS0YHI";

void setup() {
  Serial.begin(115200);
  
  // Initialize Pins
  pinMode(gasPin, INPUT);
  pinMode(lightPin, OUTPUT);
  pinMode(buzzerPin, OUTPUT);
  digitalWrite(lightPin, LOW);
  digitalWrite(buzzerPin, LOW);

  // Initialize LCD
  lcd.init();
  lcd.backlight();
  
  // Show Startup Message
  lcd.setCursor(0, 0);
  lcd.print("Gas Detector");
  lcd.setCursor(0, 1);
  lcd.print("Initializing...");
  delay(2000); 
  lcd.clear();

  while (!Serial) {
    ; // wait for serial port to connect
  }
  
  WiFi.mode(WIFI_STA); 
  ThingSpeak.begin(client);  
}

void loop() {
  // 1. Read Sensor
  int gasValue = analogRead(gasPin);
  Serial.print("Gas Value: ");
  Serial.println(gasValue);

  // 2. Update Hardware (LED/Buzzer) & LCD
  lcd.setCursor(0, 0); // Top row
  lcd.print("Gas Level: " + String(gasValue) + "   "); // Extra spaces to clear old numbers

  if(gasValue >= 3000) {
    // Danger State
    digitalWrite(lightPin, HIGH);
    digitalWrite(buzzerPin, HIGH);
    
    lcd.setCursor(0, 1); // Bottom row
    lcd.print("ALERT: LEAKAGE! ");
  } else {
    // Safe State
    digitalWrite(lightPin, LOW);
    digitalWrite(buzzerPin, LOW);
    
    lcd.setCursor(0, 1); // Bottom row
    lcd.print("Status: Safe    ");
  }

  // 3. WiFi Connection Check
  if(WiFi.status() != WL_CONNECTED){
    Serial.print("Attempting to connect to SSID: ");
    lcd.setCursor(0, 1);
    lcd.print("Wifi Connecting.");
    while(WiFi.status() != WL_CONNECTED){
      WiFi.begin(ssid, pass); 
      delay(5000);     
    } 
    Serial.println("\nConnected.");
  }
  
  // 4. Update ThingSpeak
  // Note: ThingSpeak updates take time, so the LCD might pause briefly here.
  int x = ThingSpeak.writeField(myChannelNumber, 1, gasValue, myWriteAPIKey);
  if(x == 200){
    Serial.println("Channel update successful.");
  }
  else{
    Serial.println("Problem updating channel. HTTP error code " + String(x));
  }
  
  // 5. Delay
  // ThingSpeak requires ~15 seconds between updates on free accounts.
  // During this delay, the LCD will not update new values.
  delay(2000); 
}