<template>
  <a class="shopping_cart_block" href="/order">
    <div class="shopping_cart_block__icon">
      <i class="fa fa-shopping-bag fa-2x"></i>
      <div class="shopping_cart_block__count">
        <span>{{ total}}</span>
      </div>
    </div>
    <div class="shopping_cart_block__content">
      <p class="shopping_cart_block__text">Покупки</p>
      <p class="shopping_cart_block__total"><span>{{ cost }}</span> грн.</p>
    </div>
  </a>
</template>

<script>
  import { mapGetters } from 'vuex/dist/vuex.esm';

  export default {
    name: "ShoppingCartInfo",
    computed: {
      ...mapGetters('cart', {
          total: 'countProducts',
          cost: 'costProducts'
        }
      ),
    },
    created() {
      this.$store.dispatch('cart/initCart');
    }
  }
</script>
