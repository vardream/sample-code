import 'slick-carousel';
import 'slick-carousel/slick/slick.scss';
import 'slick-carousel/slick/slick-theme.scss';

module.exports = () => {
  let slider_leaders = $(".slider_leaders");
  slider_leaders.slick({
    centerMode: true,
    centerPadding: '160px',
    arrows: false,
    dots: true,
    autoplay: true,
    slidesToShow: 1,
    infinite: true,
    dotsClass: 'mirra_slick_dots'
  });
  slider_leaders.show();
};
