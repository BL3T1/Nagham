<style>
    /* Make logo circular for admin and reception panels */
    /* Direct approach: Target images with logo in src and style them + their containers */
    
    /* Target logo images directly - highest specificity */
    [data-panel-id="admin"] img[src*="logo"],
    [data-panel-id="reception"] img[src*="logo"] {
        border-radius: 50% !important;
        width: 4rem !important;
        height: 4rem !important;
        object-fit: cover !important;
        display: block !important;
    }
    
    /* Target parent containers using adjacent and general sibling selectors */
    [data-panel-id="admin"] a[href]:has(img[src*="logo"]),
    [data-panel-id="reception"] a[href]:has(img[src*="logo"]),
    [data-panel-id="admin"] div:has(img[src*="logo"]),
    [data-panel-id="reception"] div:has(img[src*="logo"]) {
        border-radius: 50% !important;
        overflow: hidden !important;
        width: 4rem !important;
        height: 4rem !important;
        aspect-ratio: 1 / 1 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    /* Fallback: Target by class patterns */
    [data-panel-id="admin"] [class*="brand"],
    [data-panel-id="reception"] [class*="brand"],
    [data-panel-id="admin"] [class*="fi-brand"],
    [data-panel-id="reception"] [class*="fi-brand"] {
        border-radius: 50% !important;
        overflow: hidden !important;
        width: 4rem !important;
        height: 4rem !important;
        aspect-ratio: 1 / 1 !important;
    }
</style>

<script>
    // JavaScript fallback to ensure logo is circular (runs after page load)
    document.addEventListener('DOMContentLoaded', function() {
        // Find all logo images
        const logoImages = document.querySelectorAll('[data-panel-id="admin"] img[src*="logo"], [data-panel-id="reception"] img[src*="logo"]');
        
        logoImages.forEach(function(img) {
            // Make image circular
            img.style.borderRadius = '50%';
            img.style.width = '4rem';
            img.style.height = '4rem';
            img.style.objectFit = 'cover';
            img.style.display = 'block';
            
            // Make parent container circular
            const parent = img.parentElement;
            if (parent) {
                parent.style.borderRadius = '50%';
                parent.style.overflow = 'hidden';
                parent.style.width = '4rem';
                parent.style.height = '4rem';
                parent.style.aspectRatio = '1 / 1';
                parent.style.display = 'flex';
                parent.style.alignItems = 'center';
                parent.style.justifyContent = 'center';
            }
        });
    });
</script>
