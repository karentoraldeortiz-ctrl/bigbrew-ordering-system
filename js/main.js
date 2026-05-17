
const steps = document.querySelectorAll('.step');
steps.forEach(step => {
    const img = step.querySelector(".toggle-btn");
    if (!img) return;
    img.addEventListener("click", () => {
        step.classList.toggle("active");
    });
});

let currentIndex = 0;

  function getVisibleCount() {
    return window.innerWidth <= 700 ? 2 : 3;
  }

  function moveCarousel(direction) {
    const track = document.getElementById('carouselTrack');
    const cards = track.querySelectorAll('.card');
    const total = cards.length;
    const visible = getVisibleCount();
    const maxIndex = total - visible;

    currentIndex += direction;
    if (currentIndex < 0) currentIndex = 0;
    if (currentIndex > maxIndex) currentIndex = maxIndex;

    // Calculate card width + gap
    const cardWidth = cards[0].offsetWidth;
    const gap = 30;
    const offset = currentIndex * (cardWidth + gap);

    track.style.transform = `translateX(-${offset}px)`;

    // Update arrow visibility
    document.querySelector('.arrow-left').style.opacity = currentIndex === 0 ? '0' : '1';
    document.querySelector('.arrow-right').style.opacity = currentIndex === maxIndex ? '0' : '1';
  }

  // Flip card functionality
// Flip card functionality - click anywhere inside card-inner
    document.querySelectorAll('.card-inner').forEach(cardInner => {
      cardInner.addEventListener('click', () => {
        cardInner.closest('.card').classList.toggle('flip');
      });
    });
  // Recalculate on resize
  window.addEventListener('resize', () => moveCarousel(0));

  // Init arrow states
  moveCarousel(0);

  const heroSlides = document.querySelectorAll(".hero-slide");
let currentHeroSlide = 0;

if (heroSlides.length > 0) {
  setInterval(() => {
    heroSlides[currentHeroSlide].classList.remove("active");

    currentHeroSlide = (currentHeroSlide + 1) % heroSlides.length;

    heroSlides[currentHeroSlide].classList.add("active");
  }, 4000);
}