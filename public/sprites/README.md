# Pixel Art Sprite Assets for Retro Vending Machine

This directory contains pixel art sprites for the 8-bit retro vending machine interface.

## Required Sprites

### Product Sprites (32x32px PNGs)

Place these sprite files in `/public/sprites/`:

1. **juice.png** - Juice box or carton
   - Size: 32x32 pixels
   - Style: 8-bit pixel art
   - Colors: Game Boy palette (greens, dark greens, light greens)
   - Example: Small juice box with straw

2. **meal.png** - Bento box or meal container
   - Size: 32x32 pixels
   - Style: 8-bit pixel art
   - Colors: Game Boy palette
   - Example: Square bento box with dividers

3. **snack.png** - Cookie, chips, or snack package
   - Size: 32x32 pixels
   - Style: 8-bit pixel art
   - Colors: Game Boy palette
   - Example: Round cookie or chip bag

### Animation Sprites (Optional)

These sprites are used for animations but are optional - the interface works with emoji fallbacks:

4. **coin-insert.png** - 4-frame coin drop animation
   - Size: 32x32 pixels per frame
   - Frames: 4
   - Layout: Horizontal sprite sheet (128x32px total)
   - Animation: Coin falling into slot

5. **product-drop.png** - 8-frame product falling animation
   - Size: 32x32 pixels per frame
   - Frames: 8
   - Layout: Horizontal sprite sheet (256x32px total)
   - Animation: Product rotating as it falls

6. **dispensing.png** - 6-frame dispensing sequence
   - Size: 64x64 pixels per frame
   - Frames: 6
   - Layout: Horizontal sprite sheet (384x64px total)
   - Animation: Machine coils pushing product

## Color Palette

Use the Game Boy Classic green palette for authenticity:

```css
--retro-darkest: #0f380f;    /* Darkest green - primary outlines */
--retro-dark: #306230;       /* Dark green - shadows */
--retro-light: #8bac0f;      /* Light green - midtones */
--retro-lightest: #9bbc0f;   /* Lightest green - highlights */
```

## How to Create Sprites

### Option 1: Use Free Assets

Download free pixel art sprites from these sites:
- [OpenGameArt.org](https://opengameart.org/) - Search for "pixel art food drink"
- [Itch.io](https://itch.io/game-assets/free/tag-pixel) - Filter by "pixel art" and "free"
- Ensure license allows commercial use

### Option 2: Create with Aseprite

1. Download [Aseprite](https://www.aseprite.org/) (paid) or use [LibreSprite](https://libresprite.github.io/) (free)
2. Create new canvas: 32x32 pixels
3. Set color mode to Indexed with Game Boy palette
4. Draw sprite using pixel tools
5. Export as PNG

### Option 3: Create with Photoshop/GIMP

1. Create new image: 32x32 pixels
4. Set zoom to 800-1000% to see individual pixels
5. Use Pencil tool (not Brush) to draw pixels
6. Export as PNG with "Nearest Neighbor" resizing

### Option 4: AI Generation + Post-Processing

1. Use an AI image generator with prompt: "8-bit pixel art juice box, Game Boy green palette, transparent background, 32x32"
2. Import into image editor
3. Resize to 32x32 with "Nearest Neighbor" interpolation
4. Clean up pixels manually
5. Convert to Game Boy palette
6. Export as PNG

## Temporary Fallback

The retro interface currently uses emoji as placeholders:
- 🧃 for juice
- 🍱 for meals
- 🍪 for snacks

These work fine for testing! The pixel art sprites are optional for visual polish.

## File Structure

```
public/
└── sprites/
    ├── README.md (this file)
    ├── juice.png (optional)
    ├── meal.png (optional)
    ├── snack.png (optional)
    ├── coin-insert.png (optional)
    ├── product-drop.png (optional)
    └── dispensing.png (optional)
```

## Testing

The retro interface will work perfectly with emoji fallbacks. To test with actual sprites:

1. Place sprite files in `/public/sprites/`
2. Clear browser cache: `php artisan cache:clear`
3. Hard refresh browser: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)
4. The sprites should automatically replace the emoji

## Credits

If you use free assets, please credit the original authors in your project's README or credits section.
