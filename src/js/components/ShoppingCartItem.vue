<template>
  <div class="shopping_cart__item">
    <div class="shopping_cart__item__photo">
      <img
          :src="'/images/products/' + nomenclature + '.png'"
          :alt="title">
    </div>
    <div class="shopping_cart__item__body">
      <h2>{{ title }}</h2>
      <p class="nomenclature">Артикул № {{ nomenclature }}</p>
      <p class="volume">{{ volume }}</p>
      <p class="price">Цена: <span>{{ price }} грн.</span></p>
      <div class="data">
        <div class="options">
          <p class="quantity">Количество:
            <span>
              <i v-if="quantity > 1"
                 class="fa fa-minus-circle"
                 @click="onDecrement"
              >
              </i>
              {{ quantity }} шт.
              <i class="fa fa-plus-circle"
                 @click="onIncrement"
              >
              </i>
            </span>
          </p>
          <p class="cost">Сумма: <span>{{ cost }} грн.</span></p>
        </div>
        <slot name="control"></slot>
      </div>
    </div>
  </div>
</template>

<script>
  import {mapActions} from 'vuex/dist/vuex.esm';

  export default {
    name: "ShoppingCartItem",
    props: {
      id: {
        type: Number,
        required: true
      },
      nomenclature: {
        type: String,
        required: true
      },
      title: {
        type: String,
        required: true
      },
      quantity: {
        type: Number,
        required: true
      },
      volume: {
        type: String,
        default: ''
      },
      price: {
        type: Number,
        required: true
      }
    },
    computed: {
      cost() {
        return this.quantity * this.price;
      }
    },
    methods: {
      ...mapActions('cart', [
        'incrementQuantity',
        'decrementQuantity'
      ]),
      onIncrement() {
        this.incrementQuantity(this.id);
      },
      onDecrement() {
        this.decrementQuantity(this.id);
      }
    }
  }
</script>
