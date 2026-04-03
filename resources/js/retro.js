// ========================================
// RETRO VENDING MACHINE - ALPINE.JS CONTROLLER
// ========================================

function retroVending() {
    return {
        $wire: null, // Will be set by Alpine

        gameState: 'select_machine',
        balance: 0,
        todayUsage: {
            juice: 0,
            meal: 0,
            snack: 0,
        },

        // Initialize retro vending machine
        initRetro() {
            // Watch for Livewire property changes using $watch
            this.$wire.$watch('balance', (newVal, oldVal) => {
                if (newVal !== oldVal) {
                    this.balance = newVal;
                    this.animateBalanceUpdate();
                }
            });

            this.$wire.$watch('todayUsage', (newVal) => {
                if (newVal) {
                    this.todayUsage = newVal;
                }
            });

            this.$wire.$watch('gameState', (newVal) => {
                if (newVal) {
                    this.gameState = newVal;
                }
            });

            // Preload sprites
            this.preloadSprites();

            // Initialize balance from server
            this.balance = this.$wire.balance || 0;
        },

        // Animate balance update with credit counter effect
        animateBalanceUpdate() {
            // Find all balance displays and animate them
            const balanceElements = document.querySelectorAll('[x-text*="balance"]');
            balanceElements.forEach(el => {
                el.classList.add('animate-credit-pulse');
                setTimeout(() => {
                    el.classList.remove('animate-credit-pulse');
                }, 200);
            });
        },

        // Preload pixel art sprites
        preloadSprites() {
            const sprites = [
                '/sprites/juice.png',
                '/sprites/meal.png',
                '/sprites/snack.png',
                '/sprites/coin-insert.png',
                '/sprites/product-drop.png',
                '/sprites/dispensing.png',
            ];

            sprites.forEach(src => {
                const img = new Image();
                img.src = src;
            });
        },

        // Trigger dispensing animation
        triggerDispenseAnimation() {
            this.gameState = 'dispensing';

            // Auto-transition to result after animation
            setTimeout(() => {
                this.gameState = 'result';
            }, 3000);
        },

        // Handle keyboard navigation
        handleKeydown(event) {
            switch (event.key) {
                case 'ArrowUp':
                case 'ArrowDown':
                case 'ArrowLeft':
                case 'ArrowRight':
                    // Navigate between slots
                    this.navigateSlots(event.key);
                    event.preventDefault();
                    break;

                case 'Enter':
                case ' ':
                    // Select current slot
                    this.selectCurrentSlot();
                    event.preventDefault();
                    break;

                case 'Escape':
                    // Go back or cancel
                    this.goBack();
                    event.preventDefault();
                    break;
            }
        },

        // Navigate between slots (placeholder for future enhancement)
        navigateSlots(direction) {
            // This would implement keyboard navigation through slots
            // For now, it's a placeholder
            console.log('Navigate:', direction);
        },

        // Select current slot (placeholder for future enhancement)
        selectCurrentSlot() {
            // This would select the currently focused slot
            // For now, it's a placeholder
            console.log('Select current slot');
        },

        // Go back to previous screen
        goBack() {
            if (this.gameState === 'confirm') {
                Livewire.dispatch('cancelPurchase');
            } else if (this.gameState === 'select_slot') {
                Livewire.dispatch('backToMachines');
            }
        },
    };
}

// ========================================
// LIVEREACTIVE HELPERS
// ========================================

// Make retroVending available globally for Livewire components
window.retroVending = retroVending;

// ========================================
// SOUND EFFECTS (OPTIONAL - FOR FUTURE)
// ========================================

const RetroSounds = {
    // Coin insertion sound
    coinInsert() {
        // Placeholder for future sound implementation
        // Could use Web Audio API
    },

    // Button click sound
    buttonClick() {
        // Placeholder for future sound implementation
    },

    // Success sound
    success() {
        // Placeholder for future sound implementation
    },

    // Error sound
    error() {
        // Placeholder for future sound implementation
    },

    // Dispensing motor sound
    dispensing() {
        // Placeholder for future sound implementation
    },
};

// ========================================
// UTILITY FUNCTIONS
// ========================================

// Format number as pixel-style counter
function formatPixelNumber(num) {
    return num.toString().padStart(3, '0');
}

// Calculate LED blink speed based on stock level
function getLedBlinkSpeed(quantity) {
    if (quantity <= 5) return '0.5s';
    if (quantity <= 10) return '1s';
    return '2s';
}

// Get LED color class based on stock level
function getStockColorClass(quantity) {
    if (quantity === 0) return 'stock-out';
    if (quantity > 10) return 'stock-high';
    if (quantity > 5) return 'stock-medium';
    return 'stock-low';
}

// ========================================
// ACCESSIBILITY HELPERS
// ========================================

// Announce state changes to screen readers
function announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('role', 'status');
    announcement.setAttribute('aria-live', 'polite');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    document.body.appendChild(announcement);

    setTimeout(() => {
        document.body.removeChild(announcement);
    }, 1000);
}

// Check if user prefers reduced motion
function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

// ========================================
// PERFORMANCE OPTIMIZATION
// ========================================

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function for performance
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ========================================
// INITIALIZATION
// ========================================

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Add keyboard event listener
    document.addEventListener('keydown', (event) => {
        // Only handle navigation if not in an input field
        if (event.target.tagName !== 'INPUT' && event.target.tagName !== 'TEXTAREA') {
            window.retroVending?.handleKeydown?.(event);
        }
    });

    // Log initialization
    console.log('🎮 Retro Vending Machine initialized');
});

// ========================================
// EXPORTS
// ========================================

export {
    retroVending,
    RetroSounds,
    formatPixelNumber,
    getLedBlinkSpeed,
    getStockColorClass,
    announceToScreenReader,
    prefersReducedMotion,
    debounce,
    throttle,
};
