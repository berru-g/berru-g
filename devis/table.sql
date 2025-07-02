CREATE TABLE devis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(50),
  date_devis VARCHAR(50),
  client_nom VARCHAR(255),
  client_email VARCHAR(255),
  total VARCHAR(50),
  details TEXT,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);