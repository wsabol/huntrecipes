import { Carousel } from 'bootstrap';

export default function() {

    const album = document.querySelector('#family-album');
    if (album) {
        const carousel = new Carousel(album, { interval: 6000, pause: false });
        let playing = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const updatePlayback = () => {
            album.querySelector('.carousel-inner').setAttribute('aria-live', playing ? 'off' : 'polite');
            playing ? carousel.cycle() : carousel.pause();
        };
        album.addEventListener('mouseenter', () => carousel.pause());
        album.addEventListener('mouseleave', () => { if (playing) carousel.cycle(); });
        updatePlayback();
    }
}
