import Vue from 'vue/dist/vue.esm';
import DistributorsRegistration from './components/DistributorsRegistration.vue';
import './_validation_rules';

new Vue({
  el: '#appRegistration',
  components: {
    DistributorsRegistration
  }
});
