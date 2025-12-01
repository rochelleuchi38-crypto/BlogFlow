<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

$availableFonts = [
    'Arial, sans-serif',
    'Times New Roman, serif',
    'Courier New, monospace',
    'Georgia, serif',
    'Verdana, sans-serif',
    'Poppins, sans-serif',
    'Roboto, sans-serif',
    'Open Sans, sans-serif',
    'Lato, sans-serif',
    'Montserrat, sans-serif'
];
?>

<div class="font-picker-container">
    <label for="font-select">Choose your font:</label>
    <select id="font-select" class="font-select">
        <?php foreach ($availableFonts as $font): ?>
            <option value="<?= htmlspecialchars($font) ?>" style="font-family: <?= htmlspecialchars($font) ?>;">
                <?= htmlspecialchars($font) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<script>
// --- Inlined fontLoader.js functions ---
window.loadFonts = function(fonts) {
    // Remove any existing Google Font links
    document.querySelectorAll('link[rel="stylesheet"][href*="fonts.googleapis.com"]').forEach(link => {
        link.remove();
    });

    // Filter and prepare Google Fonts
    const fontFamilies = fonts
        .filter(font =>
            !font.includes('Arial') &&
            !font.includes('Times New Roman') &&
            !font.includes('Courier New') &&
            !font.includes('Georgia') &&
            !font.includes('Verdana')
        )
        .map(font => {
            const fontName = font.split(',')[0].trim();
            return fontName.replace(/ /g, '+');
        })
        .join('|');

    if (fontFamilies) {
        const link = document.createElement('link');
        link.href = `https://fonts.googleapis.com/css2?family=${fontFamilies}:wght@300;400;500;600;700&display=swap`;
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }
};

window.saveFontPreference = function(font) {
    localStorage.setItem('userFontPreference', font);
};

window.getFontPreference = function() {
    return localStorage.getItem('userFontPreference') || 'Arial, sans-serif';
};

// --- FontPicker logic ---
const fontSelect = document.getElementById('font-select');
const availableFonts = <?= json_encode($availableFonts) ?>;

// Apply saved font or default
let selectedFont = window.getFontPreference();
document.body.style.fontFamily = selectedFont;
fontSelect.value = selectedFont;

// Listen for dropdown changes
fontSelect.addEventListener('change', function() {
    selectedFont = this.value;
    window.saveFontPreference(selectedFont);
    document.body.style.fontFamily = selectedFont;
    window.loadFonts(availableFonts);
});

// Initial load of Google Fonts
window.loadFonts(availableFonts);
</script>

<style>
.font-picker-container {
    margin: 20px 0;
}

.font-select {
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 16px;
    min-width: 200px;
}

/* Apply the selected font to the dropdown options */
option {
    font-family: inherit;
}
</style>
