import Vue from 'vue/dist/vue.esm';
import Comments from './components/Comments.vue';
import './_validation_rules';

new Vue({
  el: '#appComments',
  components: {
    Comments
  }
});