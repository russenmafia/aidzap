CREATE TABLE legal_pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(50) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  content LONGTEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE faq_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(500) NOT NULL,
  answer LONGTEXT NOT NULL,
  sort_order INT DEFAULT 1,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO legal_pages (slug, title, content) VALUES
('terms', 'Terms of Service', '<p>Terms of Service content...</p>'),
('privacy', 'Privacy Policy / Datenschutz', '<p>Privacy Policy content...</p>'),
('impressum', 'Impressum', '<p>Impressum content...</p>');
