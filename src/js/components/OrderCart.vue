<template>
  <div class="shopping_cart_order">
    <shopping-cart-list></shopping-cart-list>
    <form class="contacts_data" action="#" method="post" v-on:submit.prevent="sendOrder">
      <h2>Контактные данные</h2>

      <table>
        <tr>
          <td>
            <label for="person">*Имя:</label>
          </td>
          <td>
            <input type="text" id="person" placeholder="Ваше имя"
                   name="person"
                   data-vv-as="Имя"
                   v-validate="'required|person|max:64'"
                   v-model.trim="person">
          </td>
        </tr>
        <tr v-show="errors.has('person')">
          <td></td>
          <td>
            <span>{{ errors.first('person') }}</span>
          </td>
        </tr>
        <tr>
          <td>
            <label for="email">*E-mail:</label>
          </td>
          <td>
            <input type="email" id="email" placeholder="электронная почта"
                   name="email"
                   data-vv-as="E-mail"
                   v-validate="'required|email|max:64'"
                   v-model.trim="email">
          </td>
        </tr>
        <tr v-show="errors.has('email')">
          <td></td>
          <td>
            <span>{{ errors.first('email') }}</span>
          </td>
        </tr>
        <tr>
          <td>
            <label for="phone">*Телефон:</label>
          </td>
          <td>
            <input type="tel" id="phone" placeholder="номер мобильного телефона"
                   name="phone"
                   data-vv-as="Телефон"
                   v-validate="'required|phone|max:32'"
                   v-model.trim="phone">
          </td>
        </tr>
        <tr v-show="errors.has('phone')">
          <td></td>
          <td>
            <span>{{ errors.first('phone') }}</span>
          </td>
        </tr>

        <tr>
          <td colspan="2">
            *Город:
            <label v-for="(city, index) in cities">
              <input type="radio" v-model="selected.city" :value="index" :checked="selected.city === index">
              {{ city }}
            </label>
          </td>
        </tr>

        <tr v-if="selected.city !== 0">
          <td colspan="2">
            <input type="text" placeholder="населённый пункт"
                   name="city"
                   data-vv-as="Город"
                   v-validate="'required|max:64'"
                   v-model.trim="city"
            >
          </td>
        </tr>
        <tr v-show="selected.city !== 0 && errors.has('city')">
          <td colspan="2">
            <span>{{ errors.first('city') }}</span>
          </td>
        </tr>

        <tr>
          <td colspan="2">
            *Способ доставки:
            <label v-for="(delivery, index) in deliveries">
              <input type="radio" v-model="selected.delivery" :value="index" :checked="selected.delivery === index">
              <span v-html="delivery"></span>
            </label>
          </td>
        </tr>

        <tr v-if="selected.city !== delivery">
          <td colspan="2">
            <label for="address">*{{ address_label }}</label>
          </td>
        </tr>

        <tr v-if="selected.city === 0 && delivery === 1">
          <td colspan="2">
            <textarea id="address"
                      name="address"
                      :data-vv-as="address_label"
                      v-validate="'required|max:255'"
                      v-model.trim="address"
                      :placeholder="address_placeholder">
            </textarea>
          </td>
        </tr>
        <tr v-show="selected.city === 0 && delivery === 1 && errors.has('address')">
          <td colspan="2">
            <span>{{ errors.first('address') }}</span>
          </td>
        </tr>

        <tr v-if="(selected.city === 0 && delivery === 2) || (selected.city !== 0 && delivery === 0)">
          <td colspan="2">
            <input type="text"
                   name="postal_code"
                   :data-vv-as="address_label"
                   v-validate="'required|max:64'"
                   v-model.trim="postal_code"
                   :placeholder="address_placeholder"
            >
          </td>
        </tr>
        <tr v-show="(selected.city === 0 && delivery === 2) || (selected.city !== 0 && delivery === 0) && errors.has('postal_code')">
          <td colspan="2">
            <span>{{ errors.first('postal_code') }}</span>
          </td>
        </tr>

        <tr>
          <td colspan="2">
            <button type="submit" :disabled="!locked" value="send">Заказать</button>
          </td>
        </tr>
      </table>
    </form>

  </div>
</template>

<script>
  import '../_validation_rules';

  import ShoppingCartList from './ShoppingCartList.vue';

  import { mapGetters } from 'vuex/dist/vuex.esm';

  export default {
    name: "OrderCart",
    components: {
      ShoppingCartList
    },
    data() {
      return {
        person: '',
        email: '',
        phone: '',
        selected: {
          city: 0,
          delivery: 0
        },
        cities: [
          'Киев',
          'другой'
        ],
        city: '',
        address: '',
        postal_code: '',
        deliveries_set: [
          [
            'самовывоз',
            'курьер',
            `«Новая почта»`
          ],
          [
            `«Новая почта»`
          ]
        ],
        address_labels: [
          [
            '',
            'Адрес доставки:',
            'Номер почтового отделения:'
          ],
          [
            'Номер почтового отделения:'
          ]
        ],
        address_placeholders: [
          [
            '',
            'улица, номер дома, квартира',
            'номер почтового отделения в Вашем городе'
          ],
          [
            'номер почтового отделения в Вашем городе'
          ],
        ]

      }
    },
    computed: {
      ...mapGetters('cart', {
        countProducts: 'countProducts',
        locked: 'getReceived'
      }),
      deliveries() {
        let deliveries = this.deliveries_set[this.selected.city];
        return deliveries;
      },
      delivery() {
        let deliveries = this.deliveries;
        if (deliveries.length < (this.selected.delivery + 1)) {
          this.selected.delivery = 0;
        }
        return this.selected.delivery;
      },
      address_label() {
        return this.address_labels[this.selected.city][this.selected.delivery];
      },
      address_placeholder() {
        return this.address_placeholders[this.selected.city][this.selected.delivery];
      }
    },
    methods: {
      sendOrder() {
        let store = this.$store;
        this.$validator.validateAll().then(result => {
          if (result) {
            store.commit('cart/setReceived', false);
            let data = this.prepareData();
            $.ajax({
              type: 'POST',
              url: '/api/order',
              data: data,
              dataType: 'json',
              success(data) {
                store.commit('cart/setReceived', true);
                store.commit('cart/setCheckoutStatus', true);
                store.commit('cart/resetCart');
              },
              error() {
                store.commit('cart/setReceived', true);
                store.commit('cart/logErrors', 'Ошибка при добавлении заказа');
                console.log('Error request');
              }
            });

          } else {
            store.commit('cart/logErrors', 'Ошибка валиадатора.');
          }
        });
      },
      prepareData() {
        let city = this.selected.city === 0 ? this.cities[this.selected.city] : this.city;
        let delivery_address = '';
        if (this.selected.city === 0) {
          switch (this.selected.delivery) {
            case 1:
              delivery_address = this.address;
              break;
            case 2:
              delivery_address = this.postal_code;
              break;
            default:
              delivery_address = this.deliveries_set[this.selected.city][0];
              break;
          }
        } else {
          delivery_address = this.postal_code;
        }

        return {
          person: this.person,
          email: this.email,
          phone: this.phone,
          city: city,
          delivery: this.deliveries_set[this.selected.city][this.selected.delivery],
          address: delivery_address
        };
      }
    }
  }
</script>
