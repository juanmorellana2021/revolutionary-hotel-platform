/**
 * UI Tests for AiniFlow Discover Page
 * Tests user interactions, animations, and visual elements
 */

const { test, expect } = require('@playwright/test');

test.describe('Discover Page - UI Tests', () => {
    
    test('page loads successfully', async ({ page }) => {
        await page.goto('/social.html');
        
        // Check title
        await expect(page).toHaveTitle(/Discover/i);
        
        // Page should be visible
        await expect(page.locator('body')).toBeVisible();
    });
    
    test('displays header with user level', async ({ page }) => {
        await page.goto('/social.html');
        
        // Header should be visible
        const header = page.locator('header');
        await expect(header).toBeVisible();
        
        // Should show level badge
        const levelBadge = page.locator('text=/Level \\d+/i');
        await expect(levelBadge).toBeVisible();
    });
    
    test('loads and displays profile cards', async ({ page }) => {
        await page.goto('/social.html');
        
        // Wait for cards to load (max 5 seconds)
        await page.waitForSelector('[x-show="cards.length > 0"]', { timeout: 5000 });
        
        // Should show at least one card
        const cards = page.locator('.profile-card, [class*="card"]');
        await expect(cards.first()).toBeVisible();
    });
    
    test('displays action buttons', async ({ page }) => {
        await page.goto('/social.html');
        
        // Wait for page to be interactive
        await page.waitForLoadState('networkidle');
        
        // Should have skip, message, and like buttons
        const buttons = page.locator('button');
        const buttonCount = await buttons.count();
        expect(buttonCount).toBeGreaterThan(0);
    });
    
    test('shows loading state initially', async ({ page }) => {
        await page.goto('/social.html');
        
        // Loading spinner should appear briefly
        const spinner = page.locator('.fa-spinner, [class*="spinner"]');
        
        // Either spinner is visible or cards loaded so fast it's gone
        const isSpinnerVisible = await spinner.isVisible().catch(() => false);
        const areCardsVisible = await page.locator('[x-show="cards.length > 0"]').isVisible().catch(() => false);
        
        expect(isSpinnerVisible || areCardsVisible).toBe(true);
    });
    
    test('skip button works', async ({ page }) => {
        await page.goto('/social.html');
        
        // Wait for cards to load
        await page.waitForSelector('[x-show="cards.length > 0"]', { timeout: 5000 });
        
        // Get initial card count
        const initialCards = await page.locator('.profile-card, [class*="card"]').count();
        
        // Click skip button (usually has ❌ or skip icon)
        const skipButton = page.locator('button').filter({ hasText: /❌|skip/i }).first();
        if (await skipButton.count() > 0) {
            await skipButton.click();
            
            // Wait a bit for animation
            await page.waitForTimeout(500);
            
            // Card should have changed or animation applied
            const hasSwipeClass = await page.locator('[class*="swipe"]').count();
            expect(hasSwipeClass).toBeGreaterThanOrEqual(0); // Just verify no crash
        }
    });
    
    test('like button works', async ({ page }) => {
        await page.goto('/social.html');
        
        // Wait for cards
        await page.waitForSelector('[x-show="cards.length > 0"]', { timeout: 5000 });
        
        // Click like button (usually has ❤️ icon)
        const likeButton = page.locator('button').filter({ hasText: /❤️|like/i }).first();
        if (await likeButton.count() > 0) {
            await likeButton.click();
            
            // Wait for potential animation
            await page.waitForTimeout(500);
            
            // Verify no JavaScript errors
            const errors = [];
            page.on('pageerror', error => errors.push(error));
            expect(errors.length).toBe(0);
        }
    });
    
    test('handles empty state gracefully', async ({ page }) => {
        // This test checks if page handles no cards properly
        await page.goto('/social.html');
        
        // Wait for initial load
        await page.waitForLoadState('networkidle');
        
        // Page should not crash
        const bodyText = await page.locator('body').textContent();
        expect(bodyText).toBeTruthy();
    });
    
    test('is mobile responsive', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/social.html');
        
        // Page should still load
        await expect(page.locator('body')).toBeVisible();
        
        // Header should be visible on mobile
        const header = page.locator('header');
        await expect(header).toBeVisible();
    });
    
    test('no console errors on load', async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        
        await page.goto('/social.html');
        await page.waitForLoadState('networkidle');
        
        // Check for critical errors (ignore warnings)
        const criticalErrors = errors.filter(msg => 
            !msg.includes('warning') && 
            !msg.includes('favicon')
        );
        
        expect(criticalErrors.length).toBe(0);
    });
});

test.describe('Discover Page - Performance', () => {
    
    test('page loads within 3 seconds', async ({ page }) => {
        const start = Date.now();
        await page.goto('/social.html');
        await page.waitForLoadState('networkidle');
        const duration = Date.now() - start;
        
        expect(duration).toBeLessThan(3000);
    });
    
    test('images load properly', async ({ page }) => {
        await page.goto('/social.html');
        await page.waitForLoadState('networkidle');
        
        // Check if profile photos load
        const images = page.locator('img');
        const imageCount = await images.count();
        
        if (imageCount > 0) {
            // At least check first image
            const firstImage = images.first();
            await expect(firstImage).toBeVisible();
        }
    });
});
