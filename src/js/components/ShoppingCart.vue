<template>
  <div class="shopping_cart_wrapper" v-if="visible">
    <form action="#" method="post" class="shopping_cart_window">
      <input type="hidden" name="product" :value="current">
      <header>
        <h1>Вы добавили товар в корзину</h1>
        <p><i @click="onContinueShopping" class="fa fa-close"></i></p>
      </header>
      <shopping-cart-item
          :id="current"
          :nomenclature="product.nomenclature"
          :title="product.title"
          :quantity="product.quantity"
          :volume="product.volume"
          :price="product.price - product.discount"
      >
        <div slot="control" class="controls">
          <button type="button" class="button_delete" @click.prevent="onDeleteProduct" :disabled="!locked">Удалить</button>
          <button type="button" class="button_default" @click.prevent="onContinueShopping" :disabled="!locked">Продолжить</button>
          <button type="submit" class="button_default" @click.prevent="onCheckout" :disabled="!locked">Оформить заказ</button>
        </div>
      </shopping-cart-item>
    </form>
  </div>
</template>

<script>
  import { mapGetters, mapActions } from 'vuex/dist/vuex.esm';
  import ShoppingCartItem from './ShoppingCartItem.vue';

  export default {
    name: "ShoppingCart",
    props: {
      current: {
        type: Number,
        required: true
      }
    },
    components: {
      ShoppingCartItem
    },
    computed: {
      ...mapGetters('cart', {
        visible: 'getVisibility',
        currentProduct: 'getProduct',
        changed: 'getProductChangedStatus',
        locked: 'getReceived'
      }),
      product() {
        return this.currentProduct(this.current);
      }
    },
    methods: {
      ...mapActions('cart', [
        'deleteCartsProduct',
        'updateCartsProduct',
        'hideCart'
      ]),
      onDeleteProduct() {
        this.deleteCartsProduct(this.current);
      },
      onContinueShopping() {
        if (this.locked) {
          this.updateCartsProduct({ id: this.current });
        }
      },
      onCheckout() {
        this.updateCartsProduct({ id: this.current, url: '/order' });
      }
    }
  }
</script>

<style scoped>

</style>