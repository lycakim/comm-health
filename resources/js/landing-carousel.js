// Photo Carousel Infinite Scroll Animation
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('photoCarousel');
    if (!carousel) return;

    const carouselContainer = carousel.parentElement;
    let animationId;
    let scrollPosition = 0;
    const scrollSpeed = 0.5; // pixels per frame
    let isPaused = false;

    // Calculate the width of one set of photos
    const firstSetWidth = carousel.children.length > 0 
        ? Array.from(carousel.children).slice(0, carousel.children.length / 2)
            .reduce((sum, child) => sum + child.offsetWidth + 16, 0) // 16px for gap-4
        : carousel.scrollWidth / 2;

    function animate() {
        if (!isPaused) {
            scrollPosition += scrollSpeed;
            
            // Reset position when we've scrolled one full set width
            if (scrollPosition >= firstSetWidth) {
                scrollPosition = 0;
            }
            
            carousel.style.transform = `translateX(-${scrollPosition}px)`;
        }
        
        animationId = requestAnimationFrame(animate);
    }

    // Pause on hover
    carouselContainer.addEventListener('mouseenter', () => {
        isPaused = true;
    });

    carouselContainer.addEventListener('mouseleave', () => {
        isPaused = false;
    });

    // Start animation
    animate();

    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            // Recalculate first set width on resize
            const newFirstSetWidth = carousel.children.length > 0 
                ? Array.from(carousel.children).slice(0, carousel.children.length / 2)
                    .reduce((sum, child) => sum + child.offsetWidth + 16, 0)
                : carousel.scrollWidth / 2;
            
            if (scrollPosition >= newFirstSetWidth) {
                scrollPosition = 0;
            }
        }, 250);
    });
});
