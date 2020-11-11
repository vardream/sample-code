import 'babel-polyfill';
import Vue from 'vue/dist/vue.esm';

$(document).ready(() => {

  if ($('.videoblock .videosplash .videostrip').length) {
    require.ensure(['./simpleElements'], require => {
      let simple = require('./simpleElements');
      simple();
    });
  }

  if ($('.action__block .birthday').length) {
    require.ensure(['./simpleElements'], require => {
      let simple = require('./simpleElements');
      simple();
    });
  }

  if ($('.beauty__container').length) {
    require.ensure(['./simpleElements'], require => {
      let simple = require('./simpleElements');
      simple();
    });
  }

  if ($('.prizes .btn').length && $('.prizes_block').length) {
    require.ensure(['./simpleElements'], require => {
      let simple = require('./simpleElements');
      simple();
    });
  }

  if ($('.goods_item .question_sign').length) {
    require.ensure(['./simpleElements'], require => {
      let simple = require('./simpleElements');
      simple();
    });
  }

  if ($('.chooser_wrapper').length) {
    require.ensure(['./simpleElements'], require => {
      let simple = require('./simpleElements');
      simple();
    });
  }

  if ($('#single_image').length) {
    require.ensure(['./fancyElements'], require => {
      let fancy = require('./fancyElements');
      fancy();
    });
  }

  if ($(".various").length) {
    require.ensure(['./fancyElements'], require => {
      let fancy = require('./fancyElements');
      fancy();
    });
  }

  if ($(".slider_leaders").length) {
    require.ensure(['./slickLeaders'], require => {
      let leaders = require('./slickLeaders');
      leaders();
    });
  }

  if ($("#appRegistration").length) {
    require.ensure(['./appRegistration'], require => {
      require('./appRegistration')
    })
  }

  if ($("#appQuestion").length) {
    require.ensure(['./appQuestion'], require => {
      require('./appQuestion')
    })
  }

  if ($("#appComments").length) {
    require.ensure(['./appComments'], require => {
      require('./appComments')
    })
  }

  if ($("#appShop").length) {
    require.ensure(['./shop'], require => {
      require('./shop')
    })
  }

  if ($("#appOrder").length) {
    require.ensure(['./order'], require => {
      require('./order')
    })
  }

});
