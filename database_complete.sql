-- =====================================================
-- L-SHAY Music Visualizer - Complete Database Schema
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    session_token VARCHAR(255) NULL,
    token_expires INT NULL,
    profile_data JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_session_token (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: user_sessions (Multi-Device Support)
-- NO FOREIGN KEYS for InfinityFree Compatibility
-- =====================================================
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: user_stats
-- NO FOREIGN KEYS for InfinityFree Compatibility
-- =====================================================
CREATE TABLE user_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    visualizers_created INT DEFAULT 0,
    videos_exported INT DEFAULT 0,
    total_time_minutes INT DEFAULT 0,
    last_export TIMESTAMP NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: visualizers
-- NO FOREIGN KEYS for InfinityFree Compatibility
-- =====================================================
CREATE TABLE visualizers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    settings JSON NOT NULL,
    thumbnail TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: templates
-- =====================================================
CREATE TABLE templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    genre VARCHAR(50) NOT NULL,
    style VARCHAR(50) NOT NULL,
    visualizer VARCHAR(50) NOT NULL,
    color VARCHAR(7) NOT NULL,
    secondary_color VARCHAR(7) NOT NULL,
    bg_color VARCHAR(7) NOT NULL,
    text_animation VARCHAR(50) NULL,
    particles BOOLEAN DEFAULT FALSE,
    overlay BOOLEAN DEFAULT FALSE,
    preview_data TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_genre (genre),
    INDEX idx_style (style)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: user_activity
-- NO FOREIGN KEYS for InfinityFree Compatibility
-- =====================================================
CREATE TABLE user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: text_animations
-- =====================================================
CREATE TABLE text_animations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT NULL,
    css_class VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: text_effects
-- =====================================================
CREATE TABLE text_effects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT NULL,
    css_properties JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: logo_effects
-- =====================================================
CREATE TABLE logo_effects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT NULL,
    animation_type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: background_types
-- =====================================================
CREATE TABLE background_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT NULL,
    implementation_code TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: visualizer_effects
-- =====================================================
CREATE TABLE visualizer_effects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT NULL,
    implementation_code TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: feedback
-- NO FOREIGN KEYS for InfinityFree Compatibility
-- =====================================================
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    type ENUM('suggestion', 'bug', 'feature', 'praise', 'other') NOT NULL,
    name VARCHAR(100) NULL,
    email VARCHAR(100) NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    admin_reply TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: visualizer_effects (15 Effects from config.js)
-- =====================================================
CREATE TABLE visualizer_effects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    category ENUM('waveform', 'bars', 'circle', 'particles') NOT NULL,
    description TEXT NULL,
    implementation_code TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT SAMPLE TEMPLATES (12 Templates - Matching Website)
-- =====================================================

-- Clear existing templates
DELETE FROM templates;

-- Hip-Hop Templates (3)
INSERT INTO templates (name, genre, style, visualizer, color, secondary_color, bg_color, text_animation, particles, overlay) VALUES
('Dark Trap', 'hiphop', 'dark', 'verticalBars', '#10b981', '#ef4444', '#000000', 'neon', 0, 0),
('Neon Rap', 'hiphop', 'neon', 'liquidEqualizer', '#f59e0b', '#8b5cf6', '#0a0e27', 'outerGlow', 0, 0),
('Clean Hip-Hop', 'hiphop', 'minimal', 'linearWaveform', '#ef4444', '#06b6d4', '#1a1a1a', 'none', 0, 0);

-- Lo-Fi Templates (3)
INSERT INTO templates (name, genre, style, visualizer, color, secondary_color, bg_color, text_animation, particles, overlay) VALUES
('Chill Vibes', 'lofi', 'aesthetic', 'curvedWaveform', '#8b5cf6', '#10b981', '#1a1a1a', 'gradientFill', 0, 0),
('Minimal Chill', 'lofi', 'minimal', 'solidCirclePulse', '#06b6d4', '#ef4444', '#1a1a1a', 'none', 0, 0),
('Night Study', 'lofi', 'dark', 'verticalBars', '#10b981', '#8b5cf6', '#000000', 'outerGlow', 0, 0);

-- EDM Templates (3)
INSERT INTO templates (name, genre, style, visualizer, color, secondary_color, bg_color, text_animation, particles, overlay) VALUES
('Bass Drop', 'edm', 'neon', 'liquidEqualizer', '#f59e0b', '#10b981', '#0a0e27', 'neon', 0, 0),
('Dark Techno', 'edm', 'dark', 'verticalBars', '#ef4444', '#f59e0b', '#000000', 'outerGlow', 0, 0),
('Clean EDM', 'edm', 'minimal', 'circularBars', '#8b5cf6', '#ef4444', '#1a1a1a', 'none', 0, 0);

-- Pop Templates (3)
INSERT INTO templates (name, genre, style, visualizer, color, secondary_color, bg_color, text_animation, particles, overlay) VALUES
('Pop Vibes', 'pop', 'aesthetic', 'curvedWaveform', '#06b6d4', '#8b5cf6', '#1a1a1a', 'gradientFill', 0, 0),
('Neon Pop', 'pop', 'neon', 'circularBars', '#10b981', '#06b6d4', '#0a0e27', 'neon', 0, 0),
('Clean Pop', 'pop', 'minimal', 'linearWaveform', '#f59e0b', '#10b981', '#1a1a1a', 'none', 0, 0);

-- =====================================================
-- INSERT VISUALIZER EFFECTS (15 Effects from config.js)
-- =====================================================

-- Clear existing effects
DELETE FROM visualizer_effects;

-- Waveform Effects (10)
INSERT INTO visualizer_effects (name, category, description, implementation_code) VALUES
('magneticWave', 'waveform', 'Magnetic wave effect with pull animation', 'ctx.beginPath(); for (let i = 0; i < bufferLength; i++) { const x = i * barWidth; const pull = Math.sin(i * 0.1 + animationFrame * 0.05) * 30; const y = centerY + (dataArray[i] / 2 - 64) + pull; if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y); } ctx.stroke();'),
('quantumWave', 'waveform', 'Multi-layer quantum wave effect', 'for (let layer = 0; layer < 3; layer++) { ctx.globalAlpha = 0.4; const brightness = 200 + Math.sin((layer * 120 + animationFrame) * 0.01) * 55; ctx.strokeStyle = `rgba(${brightness}, ${brightness}, ${brightness}, 0.8)`; ctx.beginPath(); for (let i = 0; i < bufferLength; i++) { const x = i * barWidth; const quantum = Math.sin(i * 0.5 + animationFrame * 0.1 + layer) * 20; const y = centerY + (dataArray[i] / 3 - 42) + quantum; if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y); } ctx.stroke(); }'),
('laserWave', 'waveform', 'Laser-like wave with glow effect', 'ctx.shadowBlur = 25; ctx.shadowColor = visualizerColor; ctx.lineWidth = 4; ctx.beginPath(); for (let i = 0; i < bufferLength; i++) { const x = i * barWidth; const y = centerY + (dataArray[i] / 2 - 64); if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y); } ctx.stroke();'),
('dimensionalWave', 'waveform', '4D dimensional wave effect', 'for (let dim = 0; dim < 4; dim++) { ctx.globalAlpha = 0.3; const offset = dim * 10; ctx.beginPath(); for (let i = 0; i < bufferLength; i++) { const x = i * barWidth + offset; const y = centerY + (dataArray[i] / 2 - 64) + offset; if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y); } ctx.stroke(); }'),
('cosmicWave', 'waveform', 'Cosmic wave with star-like points', 'ctx.shadowBlur = 20; for (let i = 0; i < bufferLength; i++) { const x = i * barWidth; const y = centerY + (dataArray[i] / 2 - 64); const brightness = 200 + Math.sin((i * 2 + animationFrame) * 0.01) * 55; ctx.strokeStyle = `rgba(${brightness}, ${brightness}, ${brightness}, 0.9)`; ctx.shadowColor = ctx.strokeStyle; ctx.beginPath(); ctx.moveTo(x - 2, y); ctx.lineTo(x + 2, y); ctx.stroke(); }'),
('echoWave', 'waveform', 'Echo wave with delay effect', 'for (let echo = 0; echo < 5; echo++) { ctx.globalAlpha = 1 - (echo / 5); const delay = echo * 5; ctx.beginPath(); for (let i = 0; i < bufferLength - delay; i++) { const x = i * barWidth; const y = centerY + (dataArray[i + delay] / 2 - 64); if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y); } ctx.stroke(); }'),
('spiralWave', 'waveform', 'Spiral wave pattern', 'ctx.beginPath(); for (let i = 0; i < bufferLength; i++) { const angle = (i / bufferLength) * Math.PI * 6 + animationFrame * 0.02; const dist = 50 + i * 1.5 + dataArray[i] / 4; const x = centerX + Math.cos(angle) * dist; const y = centerY + Math.sin(angle) * dist; if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y); } ctx.stroke();'),
('electricWave', 'waveform', 'Electric wave with random noise', 'ctx.shadowBlur = 15; ctx.shadowColor = visualizerColor; ctx.lineWidth = 2; ctx.beginPath(); for (let i = 0; i < bufferLength; i++) { const x = i * barWidth; const y = centerY + (dataArray[i] / 2 - 64) + (Math.random() - 0.5) * 20; if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y); } ctx.stroke();'),
('tornadoWave', 'waveform', 'Tornado spiral effect', 'for (let i = 0; i < bufferLength; i++) { const angle = (i / bufferLength) * Math.PI * 8 + animationFrame * 0.05; const radius = 30 + i * 0.5 + dataArray[i] / 5; const height = (i / bufferLength) * canvas.height; const x = centerX + Math.cos(angle) * radius; const y = height; ctx.beginPath(); ctx.arc(x, y, 2, 0, Math.PI * 2); ctx.fill(); }'),
('crystalWave', 'waveform', 'Crystal-like geometric wave', 'ctx.lineWidth = 1; for (let i = 0; i < bufferLength; i += 5) { const x = i * barWidth; const y = centerY + (dataArray[i] / 2 - 64); const size = dataArray[i] / 20; ctx.save(); ctx.translate(x, y); ctx.rotate(animationFrame * 0.02); ctx.beginPath(); for (let j = 0; j < 6; j++) { const angle = (j / 6) * Math.PI * 2; const px = Math.cos(angle) * size; const py = Math.sin(angle) * size; if (j === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py); } ctx.closePath(); ctx.stroke(); ctx.restore(); }');

-- Bar Effects (5)
INSERT INTO visualizer_effects (name, category, description, implementation_code) VALUES
('neonBars', 'bars', 'Neon glowing bars', 'ctx.shadowBlur = 20; for (let i = 0; i < bufferLength; i++) { const barHeight = dataArray[i]; const x = i * barWidth; const y = canvas.height - barHeight; const brightness = 200 + Math.sin((i / bufferLength) * Math.PI * 2) * 55; ctx.fillStyle = `rgba(${brightness}, ${brightness}, ${brightness}, 0.9)`; ctx.shadowColor = ctx.fillStyle; ctx.fillRect(x, y, barWidth - 2, barHeight); }'),
('liquidBars', 'bars', 'Liquid-like flowing bars', 'for (let i = 0; i < bufferLength; i++) { const barHeight = dataArray[i]; const x = i * barWidth; const wobble = Math.sin(i * 0.3 + animationFrame * 0.1) * 10; const y = canvas.height - barHeight + wobble; const gradient = ctx.createLinearGradient(x, y, x, canvas.height); gradient.addColorStop(0, visualizerColor); gradient.addColorStop(1, secondaryColor); ctx.fillStyle = gradient; ctx.fillRect(x, y, barWidth - 2, barHeight); }'),
('fireBars', 'bars', 'Fire-like bars with gradient', 'for (let i = 0; i < bufferLength; i++) { const barHeight = dataArray[i]; const x = i * barWidth; const y = canvas.height - barHeight; const gradient = ctx.createLinearGradient(x, y, x, canvas.height); gradient.addColorStop(0, "#ffffff"); gradient.addColorStop(0.5, "#f0f0f0"); gradient.addColorStop(1, "#e8e8e8"); ctx.fillStyle = gradient; ctx.shadowBlur = 15; ctx.shadowColor = "#ffffff"; ctx.fillRect(x, y, barWidth - 2, barHeight); }'),
('iceBars', 'bars', 'Ice-like crystalline bars', 'for (let i = 0; i < bufferLength; i++) { const barHeight = dataArray[i]; const x = i * barWidth; const y = canvas.height - barHeight; const gradient = ctx.createLinearGradient(x, y, x, canvas.height); gradient.addColorStop(0, "#ffffff"); gradient.addColorStop(0.5, "#f5f5f5"); gradient.addColorStop(1, "#e8e8e8"); ctx.fillStyle = gradient; ctx.shadowBlur = 10; ctx.shadowColor = "#ffffff"; ctx.fillRect(x, y, barWidth - 2, barHeight); }'),
('rainbowBars', 'bars', 'Rainbow colored bars', 'for (let i = 0; i < bufferLength; i++) { const barHeight = dataArray[i]; const x = i * barWidth; const y = canvas.height - barHeight; const brightness = 200 + Math.sin(((i / bufferLength) * Math.PI * 2 + animationFrame * 0.01)) * 55; ctx.fillStyle = `rgba(${brightness}, ${brightness}, ${brightness}, 0.9)`; ctx.fillRect(x, y, barWidth - 2, barHeight); }');

-- =====================================================
-- NEW LOGO EFFECTS TABLE
-- =====================================================
CREATE TABLE logo_effects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    category ENUM('glow', 'animation', 'transform', 'filter') NOT NULL,
    description TEXT NULL,
    css_properties JSON NOT NULL,
    animation_keyframes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Logo Effects (8 Working Effects)
INSERT INTO logo_effects (name, category, description, css_properties, animation_keyframes) VALUES
('none', 'filter', 'No effect applied', '{}', NULL),
('glow', 'glow', 'Soft glow effect around logo', '{"filter": "drop-shadow(0 0 20px currentColor)", "opacity": "0.9"}', NULL),
('neonGlow', 'glow', 'Bright neon glow effect', '{"filter": "drop-shadow(0 0 30px #00ff88) drop-shadow(0 0 60px #00ff88)", "opacity": "1"}', NULL),
('pulse', 'animation', 'Pulsing scale animation', '{"animation": "logoPulse 2s ease-in-out infinite"}', '@keyframes logoPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }'),
('floating', 'animation', 'Gentle floating motion', '{"animation": "logoFloat 3s ease-in-out infinite"}', '@keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }'),
('electric', 'glow', 'Electric blue glow with flicker', '{"filter": "drop-shadow(0 0 25px #0099ff) brightness(1.2)", "animation": "logoElectric 0.5s ease-in-out infinite alternate"}', '@keyframes logoElectric { 0% { filter: drop-shadow(0 0 25px #0099ff) brightness(1.2); } 100% { filter: drop-shadow(0 0 35px #0099ff) brightness(1.4); } }'),
('hologram', 'filter', 'Holographic shimmer effect', '{"filter": "hue-rotate(180deg) saturate(1.5)", "animation": "logoHologram 4s linear infinite"}', '@keyframes logoHologram { 0% { filter: hue-rotate(0deg) saturate(1.5); } 100% { filter: hue-rotate(360deg) saturate(1.5); } }'),
('vintage', 'filter', 'Vintage sepia tone effect', '{"filter": "sepia(0.8) contrast(1.2) brightness(0.9)", "opacity": "0.85"}', NULL);

-- =====================================================
-- NEW TEXT EFFECTS TABLE
-- =====================================================
CREATE TABLE text_effects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    category ENUM('shadow', 'glow', 'gradient', 'stroke', 'transform') NOT NULL,
    description TEXT NULL,
    css_properties JSON NOT NULL,
    canvas_implementation TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Text Effects (10 Working Effects)
INSERT INTO text_effects (name, category, description, css_properties, canvas_implementation) VALUES
('none', 'transform', 'No effect applied', '{}', 'ctx.fillText(text, x, y);'),
('shadow', 'shadow', 'Drop shadow effect', '{"textShadow": "3px 3px 6px rgba(0,0,0,0.8)"}', 'ctx.shadowColor = "rgba(0,0,0,0.8)"; ctx.shadowBlur = 6; ctx.shadowOffsetX = 3; ctx.shadowOffsetY = 3; ctx.fillText(text, x, y);'),
('glow', 'glow', 'Soft glow around text', '{"textShadow": "0 0 20px currentColor"}', 'ctx.shadowColor = ctx.fillStyle; ctx.shadowBlur = 20; ctx.fillText(text, x, y);'),
('neon', 'glow', 'Bright neon glow effect', '{"textShadow": "0 0 10px #00ff88, 0 0 20px #00ff88, 0 0 30px #00ff88"}', 'ctx.shadowColor = "#00ff88"; ctx.shadowBlur = 30; ctx.strokeStyle = "#00ff88"; ctx.lineWidth = 2; ctx.strokeText(text, x, y); ctx.fillText(text, x, y);'),
('outerGlow', 'glow', 'Outer glow with multiple layers', '{"textShadow": "0 0 5px currentColor, 0 0 10px currentColor, 0 0 15px currentColor"}', 'for(let i = 0; i < 3; i++) { ctx.shadowColor = ctx.fillStyle; ctx.shadowBlur = 5 + (i * 5); ctx.fillText(text, x, y); }'),
('gradientFill', 'gradient', 'Gradient fill effect', '{"background": "linear-gradient(45deg, #ff6b6b, #4ecdc4)", "webkitBackgroundClip": "text", "webkitTextFillColor": "transparent"}', 'const gradient = ctx.createLinearGradient(x, y - fontSize, x, y); gradient.addColorStop(0, "#ff6b6b"); gradient.addColorStop(1, "#4ecdc4"); ctx.fillStyle = gradient; ctx.fillText(text, x, y);'),
('stroke', 'stroke', 'Outline stroke effect', '{"webkitTextStroke": "2px currentColor", "webkitTextFillColor": "transparent"}', 'ctx.strokeStyle = ctx.fillStyle; ctx.lineWidth = 2; ctx.strokeText(text, x, y);'),
('emboss', 'shadow', 'Embossed 3D effect', '{"textShadow": "1px 1px 0px #ccc, 2px 2px 0px #c9c9c9, 3px 3px 0px #bbb"}', 'ctx.shadowColor = "#ccc"; ctx.shadowBlur = 0; ctx.shadowOffsetX = 1; ctx.shadowOffsetY = 1; ctx.fillText(text, x, y); ctx.shadowColor = "#bbb"; ctx.shadowOffsetX = 3; ctx.shadowOffsetY = 3; ctx.fillText(text, x, y);'),
('fire', 'gradient', 'Fire gradient effect', '{"background": "linear-gradient(0deg, #ff4500, #ffa500, #ffff00)", "webkitBackgroundClip": "text", "webkitTextFillColor": "transparent"}', 'const gradient = ctx.createLinearGradient(x, y, x, y - fontSize); gradient.addColorStop(0, "#ff4500"); gradient.addColorStop(0.5, "#ffa500"); gradient.addColorStop(1, "#ffff00"); ctx.fillStyle = gradient; ctx.fillText(text, x, y);'),
('chrome', 'gradient', 'Chrome metallic effect', '{"background": "linear-gradient(0deg, #eee, #999, #eee)", "webkitBackgroundClip": "text", "webkitTextFillColor": "transparent"}', 'const gradient = ctx.createLinearGradient(x, y, x, y - fontSize); gradient.addColorStop(0, "#eee"); gradient.addColorStop(0.5, "#999"); gradient.addColorStop(1, "#eee"); ctx.fillStyle = gradient; ctx.fillText(text, x, y);');

-- =====================================================
-- NEW BACKGROUND EFFECTS TABLE
-- =====================================================
CREATE TABLE background_effects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    category ENUM('gradient', 'pattern', 'animation', 'particle') NOT NULL,
    description TEXT NULL,
    css_properties JSON NOT NULL,
    canvas_implementation TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Background Effects (12 Working Effects)
INSERT INTO background_effects (name, category, description, css_properties, canvas_implementation) VALUES
('solid', 'gradient', 'Solid color background', '{"background": "var(--bg-color)"}', 'ctx.fillStyle = bgColor; ctx.fillRect(0, 0, canvas.width, canvas.height);'),
('linearGradient', 'gradient', 'Linear gradient background', '{"background": "linear-gradient(135deg, var(--bg-color1), var(--bg-color2))"}', 'const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height); gradient.addColorStop(0, bgColor1); gradient.addColorStop(1, bgColor2); ctx.fillStyle = gradient; ctx.fillRect(0, 0, canvas.width, canvas.height);'),
('radialGradient', 'gradient', 'Radial gradient background', '{"background": "radial-gradient(circle, var(--bg-color1), var(--bg-color2))"}', 'const gradient = ctx.createRadialGradient(canvas.width/2, canvas.height/2, 0, canvas.width/2, canvas.height/2, Math.max(canvas.width, canvas.height)/2); gradient.addColorStop(0, bgColor1); gradient.addColorStop(1, bgColor2); ctx.fillStyle = gradient; ctx.fillRect(0, 0, canvas.width, canvas.height);'),
('stars', 'particle', 'Animated starfield background', '{}', 'for(let i = 0; i < 100; i++) { const x = Math.random() * canvas.width; const y = Math.random() * canvas.height; const brightness = Math.random() * 0.8 + 0.2; ctx.fillStyle = `rgba(255, 255, 255, ${brightness})`; ctx.fillRect(x, y, 1, 1); }'),
('particles', 'particle', 'Floating particles effect', '{}', 'for(let i = 0; i < 50; i++) { const x = (Math.random() * canvas.width + animationFrame * 0.5) % canvas.width; const y = Math.random() * canvas.height; const size = Math.random() * 3 + 1; ctx.fillStyle = `rgba(16, 185, 129, ${Math.random() * 0.5 + 0.3})`; ctx.beginPath(); ctx.arc(x, y, size, 0, Math.PI * 2); ctx.fill(); }'),
('waves', 'animation', 'Animated wave pattern', '{}', 'for(let i = 0; i < canvas.width; i += 10) { const y = canvas.height/2 + Math.sin((i + animationFrame) * 0.01) * 50; ctx.fillStyle = `rgba(16, 185, 129, 0.1)`; ctx.fillRect(i, y, 10, canvas.height - y); }'),
('grid', 'pattern', 'Grid pattern overlay', '{}', 'ctx.strokeStyle = "rgba(255, 255, 255, 0.1)"; ctx.lineWidth = 1; for(let i = 0; i < canvas.width; i += 50) { ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, canvas.height); ctx.stroke(); } for(let i = 0; i < canvas.height; i += 50) { ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(canvas.width, i); ctx.stroke(); }'),
('hexagon', 'pattern', 'Hexagonal pattern', '{}', 'const size = 30; for(let x = 0; x < canvas.width + size; x += size * 1.5) { for(let y = 0; y < canvas.height + size; y += size * Math.sqrt(3)) { const offsetX = (y / (size * Math.sqrt(3))) % 2 === 0 ? 0 : size * 0.75; ctx.strokeStyle = "rgba(255, 255, 255, 0.1)"; ctx.beginPath(); for(let i = 0; i < 6; i++) { const angle = (i * Math.PI) / 3; const px = x + offsetX + Math.cos(angle) * size; const py = y + Math.sin(angle) * size; if(i === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py); } ctx.closePath(); ctx.stroke(); } }'),
('plasma', 'animation', 'Plasma effect background', '{}', 'for(let x = 0; x < canvas.width; x += 4) { for(let y = 0; y < canvas.height; y += 4) { const value = Math.sin(x * 0.01 + animationFrame * 0.02) + Math.sin(y * 0.01 + animationFrame * 0.03) + Math.sin((x + y) * 0.01 + animationFrame * 0.01); const color = Math.floor((value + 3) * 42.5); ctx.fillStyle = `hsl(${color}, 70%, 50%)`; ctx.fillRect(x, y, 4, 4); } }'),
('matrix', 'animation', 'Matrix rain effect', '{}', 'ctx.fillStyle = "rgba(0, 255, 0, 0.1)"; ctx.font = "12px monospace"; for(let i = 0; i < 20; i++) { const x = i * 30; const y = (animationFrame * 2 + i * 50) % canvas.height; ctx.fillText(String.fromCharCode(0x30A0 + Math.random() * 96), x, y); }'),
('nebula', 'gradient', 'Nebula-like gradient effect', '{}', 'const gradient = ctx.createRadialGradient(canvas.width * 0.3, canvas.height * 0.3, 0, canvas.width * 0.7, canvas.height * 0.7, canvas.width); gradient.addColorStop(0, "rgba(138, 43, 226, 0.8)"); gradient.addColorStop(0.5, "rgba(75, 0, 130, 0.6)"); gradient.addColorStop(1, "rgba(25, 25, 112, 0.4)"); ctx.fillStyle = gradient; ctx.fillRect(0, 0, canvas.width, canvas.height);'),
('aurora', 'animation', 'Aurora borealis effect', '{}', 'for(let i = 0; i < 5; i++) { const gradient = ctx.createLinearGradient(0, canvas.height * 0.3, 0, canvas.height * 0.7); gradient.addColorStop(0, `hsla(${120 + i * 60 + animationFrame * 0.5}, 70%, 50%, 0)`); gradient.addColorStop(0.5, `hsla(${120 + i * 60 + animationFrame * 0.5}, 70%, 50%, 0.3)`); gradient.addColorStop(1, `hsla(${120 + i * 60 + animationFrame * 0.5}, 70%, 50%, 0)`); ctx.fillStyle = gradient; ctx.fillRect(i * canvas.width / 5, 0, canvas.width / 5, canvas.height); }');

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check all tables
SELECT 'Tables Created' as Status;
SHOW TABLES;

-- Check templates count
SELECT COUNT(*) as 'Total Templates (Should be 12)' FROM templates;

-- Check visualizer effects count
SELECT COUNT(*) as 'Total Visualizer Effects (Should be 15)' FROM visualizer_effects;

-- Check logo effects count
SELECT COUNT(*) as 'Total Logo Effects (Should be 8)' FROM logo_effects;

-- Check text effects count
SELECT COUNT(*) as 'Total Text Effects (Should be 10)' FROM text_effects;

-- Check background effects count
SELECT COUNT(*) as 'Total Background Effects (Should be 12)' FROM background_effects;

-- Check templates by genre
SELECT genre, COUNT(*) as count FROM templates GROUP BY genre;

-- Check templates by style
SELECT style, COUNT(*) as count FROM templates GROUP BY style;

-- Check effects by category
SELECT category, COUNT(*) as count FROM visualizer_effects GROUP BY category;

-- Check logo effects by category
SELECT category, COUNT(*) as count FROM logo_effects GROUP BY category;

-- Check text effects by category
SELECT category, COUNT(*) as count FROM text_effects GROUP BY category;

-- Check background effects by category
SELECT category, COUNT(*) as count FROM background_effects GROUP BY category;

-- Show sample data
SELECT 'Sample Logo Effects:' as Info;
SELECT name, category, description FROM logo_effects LIMIT 5;

SELECT 'Sample Text Effects:' as Info;
SELECT name, category, description FROM text_effects LIMIT 5;

SELECT 'Sample Background Effects:' as Info;
SELECT name, category, description FROM background_effects LIMIT 5;

-- =====================================================
-- INSERT TEXT ANIMATIONS (12 Total)
-- =====================================================
INSERT INTO text_animations (name, description, css_class) VALUES
('none', 'No animation', 'none'),
('fadeIn', 'Fade in animation', 'fade-in'),
('slideUp', 'Slide up animation', 'slide-up'),
('slideDown', 'Slide down animation', 'slide-down'),
('slideLeft', 'Slide left animation', 'slide-left'),
('slideRight', 'Slide right animation', 'slide-right'),
('bounce', 'Bounce animation', 'bounce'),
('pulse', 'Pulse animation', 'pulse'),
('glow', 'Glow animation', 'glow'),
('typewriter', 'Typewriter effect', 'typewriter'),
('3d', '3D text effect', '3d-text'),
('matrix', 'Matrix rain effect', 'matrix-rain');

-- =====================================================
-- INSERT TEXT EFFECTS (10 Total)
-- =====================================================
INSERT INTO text_effects (name, description, css_properties) VALUES
('none', 'No effect', '{}'),
('shadow', 'Drop shadow effect', '{"textShadow": "2px 2px 4px rgba(0,0,0,0.5)"}'),
('outerGlow', 'Outer glow effect', '{"textShadow": "0 0 10px currentColor"}'),
('neon', 'Neon glow effect', '{"textShadow": "0 0 5px currentColor, 0 0 10px currentColor, 0 0 15px currentColor"}'),
('gradientFill', 'Gradient fill effect', '{"background": "linear-gradient(45deg, #ff0000, #00ff00)", "webkitBackgroundClip": "text", "webkitTextFillColor": "transparent"}'),
('stroke', 'Text stroke effect', '{"webkitTextStroke": "2px currentColor", "webkitTextFillColor": "transparent"}'),
('3dText', '3D text effect', '{"textShadow": "1px 1px 0 #ccc, 2px 2px 0 #c9c9c9, 3px 3px 0 #bbb, 4px 4px 0 #b9b9b9"}'),
('pulse', 'Pulse effect', '{"animation": "pulse 2s infinite"}'),
('rainbow', 'Rainbow effect', '{"background": "linear-gradient(45deg, red, orange, yellow, green, blue, indigo, violet)", "webkitBackgroundClip": "text", "webkitTextFillColor": "transparent"}'),
('fire', 'Fire effect', '{"textShadow": "0 0 5px #ff6600, 0 0 10px #ff6600, 0 0 15px #ff6600, 0 0 20px #ff3300"}');

-- =====================================================
-- INSERT LOGO EFFECTS (8 Total)
-- =====================================================
INSERT INTO logo_effects (name, description, animation_type) VALUES
('none', 'No effect', 'none'),
('glow', 'Glow effect', 'filter'),
('neonGlow', 'Neon glow effect', 'filter'),
('shadow', 'Drop shadow effect', 'filter'),
('pulse', 'Pulse animation', 'transform'),
('rotate', 'Rotate animation', 'transform'),
('bounce', 'Bounce animation', 'transform'),
('floating', 'Floating animation', 'transform');

-- =====================================================
-- INSERT BACKGROUND TYPES (30 Total)
-- =====================================================
INSERT INTO background_types (name, category, description) VALUES
('gradient', 'basic', 'Linear gradient background'),
('radialGradient', 'basic', 'Radial gradient background'),
('solid', 'basic', 'Solid color background'),
('audioWave', 'reactive', 'Audio-reactive wave background'),
('particleField', 'animated', 'Animated particle field'),
('geometricPattern', 'pattern', 'Geometric pattern background'),
('colorShift', 'animated', 'Color shifting background'),
('movingGradient', 'animated', 'Moving gradient background'),
('waveGrid', 'pattern', 'Wave grid pattern'),
('digitalMatrixTerrain', 'animated', 'Digital matrix terrain'),
('neonGrid', 'neon', 'Neon grid background'),
('galaxySpiral', 'space', 'Galaxy spiral background'),
('liquidMetal', 'animated', 'Liquid metal effect'),
('cyberpunkCity', 'cyberpunk', 'Cyberpunk city background'),
('quantumField', 'sci-fi', 'Quantum field effect'),
('holographicWaves', 'hologram', 'Holographic waves'),
('crystalCave', 'nature', 'Crystal cave background'),
('digitalRain', 'matrix', 'Digital rain effect'),
('plasmaBurst', 'energy', 'Plasma burst effect'),
('neuralNetwork', 'tech', 'Neural network visualization'),
('cosmicDust', 'space', 'Cosmic dust background'),
('electricStorm', 'energy', 'Electric storm effect'),
('fractalMandala', 'fractal', 'Fractal mandala pattern'),
('dataStream', 'tech', 'Data stream visualization'),
('vortexPortal', 'portal', 'Vortex portal effect'),
('binaryMatrix', 'matrix', 'Binary matrix background'),
('prismRefraction', 'light', 'Prism light refraction'),
('synthwaveGrid', 'retro', 'Synthwave grid background'),
('image', 'custom', 'Custom image background'),
('auraBurst', 'energy', 'Aura burst with pulsing energy');

-- =====================================================
-- VERIFICATION QUERIES - UPDATED COUNTS
-- =====================================================

-- Check all tables
SELECT 'Updated Tables Created' as Status;
SHOW TABLES;

-- Check templates count (should be 12)
SELECT COUNT(*) as 'Total Templates (12)' FROM templates;

-- Check text animations count (should be 12)
SELECT COUNT(*) as 'Total Text Animations (12)' FROM text_animations;

-- Check text effects count (should be 10)
SELECT COUNT(*) as 'Total Text Effects (10)' FROM text_effects;

-- Check logo effects count (should be 8)
SELECT COUNT(*) as 'Total Logo Effects (8)' FROM logo_effects;

-- Check background types count (should be 30)
SELECT COUNT(*) as 'Total Background Types (30)' FROM background_types;

-- Check visualizer effects count (should be 15)
SELECT COUNT(*) as 'Total Visualizer Effects (15)' FROM visualizer_effects;

-- Total effects calculation: 12 + 10 + 8 + 30 = 60 effects
SELECT 
    (SELECT COUNT(*) FROM text_animations) +
    (SELECT COUNT(*) FROM text_effects) +
    (SELECT COUNT(*) FROM logo_effects) +
    (SELECT COUNT(*) FROM background_types) as 'Total Effects (Should be 60)';
