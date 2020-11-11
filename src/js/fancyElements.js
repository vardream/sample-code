import fancybox from 'fancybox/dist/js/jquery.fancybox.cjs';
import 'fancybox/dist/scss/jquery.fancybox.scss'

module.exports = () => {
  fancybox($);
  if ($('#single_image')) {
    $('#single_image').fancybox({
      openEffect: 'elastic',
      closeEffect: 'elastic'
    });
  }

  if ($(".various")) {
    $(".various").fancybox({
      maxWidth: 800,
      maxHeight: 600,
      fitToView: false,
      width: '100%',
      height: '100%',
      margin: 8,
      padding: 8,
      autoSize: false,
      closeClick: false,
      openEffect: 'none',
      closeEffect: 'none'
    });
  }
};
