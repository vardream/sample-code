<template>
  <div class="shopping_cart__list">
    <h2>Ваши товары</h2>
    <div class="shopping_cart__list__items">
      <form v-for="product in products" action="#" method="post">
        <shopping-cart-item
            :id = "product.id"
            :nomenclature = "product.nomenclature"
            :title = "product.title"
            :quantity = "product.quantity"
            :volume = "product.volume"
            :price = "product.price - product.discount"
        >
          <div slot="control" class="controls">
            <button type="button" class="button_delete" @click.prevent="onDeleteProduct(product.id)" :disabled="!locked">Удалить</button>
          </div>
        </shopping-cart-item>
      </form>
      <div class="shopping_cart__summary">
        <p class="total_cost">Сумма, (без учёта стоимости доставки): <span>{{ cost }} грн.</span></p>
      </div>
    </div>
  </div>
</template>

<script>
  import ShoppingCartItem from './ShoppingCartItem.vue';

  import { mapGetters, mapActions } from 'vuex/dist/vuex.esm';

  export default {
    name: "ShoppingCartList",
    components: {
      ShoppingCartItem
    },
    data() {
      return {
        changed: false
      }
    },
    computed: {
      ...mapGetters( 'cart', {
        products: 'getProducts',
        cost: 'costProducts',
        locked: 'getReceived'
      })
    },
    methods: {
      ...mapActions( 'cart', [
        'deleteCartsProduct',
        'updateChanged'
      ]),
      onDeleteProduct(id) {
        this.deleteCartsProduct(id);
      }
    },
    created() {
      this.$store.commit('cart/setListMode', true);
    }
  }
</script>
