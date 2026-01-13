// Initialize tooltips
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

// Floating animation for feature cards
document.querySelectorAll('.feature-card').forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`;
});

// Auto-advance carousel
document.addEventListener('DOMContentLoaded', function() {
    const myCarousel = document.querySelector('#jobCarousel');
    if(myCarousel) {
        const carousel = new bootstrap.Carousel(myCarousel, {
            interval: 5000
        });
    }
});

// Pulse animation
document.querySelectorAll('.pulse').forEach(element => {
    element.addEventListener('mouseover', function() {
        this.classList.add('animate__animated', 'animate__pulse');
    });
    
    element.addEventListener('mouseout', function() {
        this.classList.remove('animate__animated', 'animate__pulse');
    });
});