-- Add settings table for storing key-value configuration
CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(255) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL
);

-- Insert default OTP verification setting as enabled (1)
INSERT INTO settings (setting_key, setting_value) VALUES ('otp_verification_enabled', '1')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
