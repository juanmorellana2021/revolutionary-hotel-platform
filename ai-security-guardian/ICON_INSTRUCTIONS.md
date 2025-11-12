# AI Security Guardian Icon

Since we can't generate images directly, here's how to create your icon:

## Quick Icon Options:

### Option 1: Use an Emoji Icon (Fastest)
Download a shield emoji as PNG:
1. Go to https://emojipedia.org/shield/
2. Download PNG version
3. Resize to 128x128px
4. Save as `icon.png` in ai-security-guardian folder

### Option 2: Free Icon Generator
1. Go to https://www.canva.com (free)
2. Create 128x128px design
3. Add shield icon 🛡️ with orange/red gradient
4. Download as PNG

### Option 3: Use This SVG (Convert to PNG)
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128">
  <defs>
    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#f97316;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#dc2626;stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="128" height="128" fill="#1e293b" rx="20"/>
  <path d="M64 20 L90 35 L90 60 Q90 85 64 100 Q38 85 38 60 L38 35 Z" 
        fill="url(#gradient)" stroke="white" stroke-width="3"/>
  <text x="64" y="72" font-family="Arial" font-size="48" font-weight="bold" 
        fill="white" text-anchor="middle">AI</text>
</svg>
```

For now, we'll package without an icon (VS Code will use a default).
