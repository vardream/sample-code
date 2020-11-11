/** state
 *
 * @type {{items: Array, initialized: boolean, loaded: boolean, visible: boolean, received: boolean, checkoutStatus: boolean, list: boolean, errors: Array}}
 */
const state = {
  // Список товаров в корзине
  // {id, nomenclature, title, quantity, volume, price, discount}
  items: [],

  // Флаги:
  // Запрос на инициализацию корзины отправлен
  initialized: false,
  // Список товаров в корзине загружен
  loaded: false,
  // Карточка добавления товара в корзину открыта
  visible: false,
  // Ответ от сервера получен
  received: false,
  //
  checkoutStatus: false,
  // Режим отображения в виде списка
  list: false,

  // Массив с данными об ошибках
  errors: []
};
/** getters
 *
 * @type {{getInitialized: (function(*): boolean), getLoaded: (function(*): boolean), getReceived: (function(*): boolean), getVisibility: (function(*): boolean), getCheckoutStatus: (function(*): boolean), getListMode: (function(*): boolean), getProductChangedStatus: (function(*): function(*): boolean), getProduct: (function(*): function(*): *), getProducts: (function(*): Array), countProducts: (function(*): number), costProducts: (function(*): number)}}
 */
const getters = {
  getInitialized: state => state.initialized,
  getLoaded: state => state.loaded,
  getReceived: state => state.received,
  getVisibility: state => state.visible,
  getCheckoutStatus: state => state.checkoutStatus,
  getListMode: state => state.list,
  getProductChangedStatus: state => id => {
    let product = state.items.find(item => item.id === id);
    return product === undefined ? false : product.changed;
  },
  getProduct: state => id => state.items.find(item => item.id === id),
  getProducts: state => state.items,
  countProducts: state => state.items.length,
  costProducts: state => {
    let cost = 0;
    state.items.forEach(item => {
      cost += (item.price - item.discount) * item.quantity;
    });
    return cost;
  }
};
/** mutations
 *
 * @type {{logErrors(*, *=): void, setInitialized(*, *): void, initCart(*, *): void, setLoaded(*, *): void, setReceived(*, *): void, setListMode(*, *): void, setCheckoutStatus(*, *): void, pushCartsProduct(*, *=): void, removeCartsProduct(*, *=): void, setCartState(*, *): void, incrementProductQuantity(*, *): void, decrementProductQuantity(*, *): void, resetCart(*): void}}
 */
const mutations = {
  logErrors(state, message) {
    state.errors.push(message);
  },
  setInitialized(state, value) {
    state.initialized = value;
  },
  initCart(state, values) {
    state.items = values;
    state.items.forEach(item => {
      item.changed = false;
    })
  },
  setLoaded(state, value) {
    state.loaded = value;
  },
  setReceived(state, value) {
    state.received = value;
  },
  setListMode(state, value) {
    state.list = value;
  },
  setCheckoutStatus(state, value) {
    state.checkoutStatus = value;
  },
  pushCartsProduct(state, product) {
    product.changed = false;
    state.items.push(product);
  },
  removeCartsProduct(state, index) {
    state.items.splice(index, 1);
  },
  setCartState(state, visibility) {
    state.visible = visibility;
  },
  incrementProductQuantity(state, product) {
    product.quantity += 1;
    product.changed = true;
  },
  decrementProductQuantity(state, product) {
    if (product.quantity > 1) {
      product.quantity -= 1;
      product.changed = true;
    }
  },
  resetCart(state) {
    state.items = [];
  }
};

/** actions
 *
 * @type {{initCart(*): void, showCart(*): void, hideCart(*): void, addCartsProduct(*, *): void, deleteCartsProduct(*, *=): void, updateCartsProduct(*, {id?: *, url?: *}): void, updateChanged(*, {product?: *, method?: *}): void, incrementQuantity(*=, *=): void, decrementQuantity(*=, *=): void}}
 */
const actions = {
  /** Инициализация корзины */
  initCart(context) {
    if (!context.getters.getInitialized) {
      context.commit('setReceived', false);
      context.commit('setInitialized', true);
      $.ajax({
        type: 'GET',
        url: '/api/cart',
        data: null,
        dataType: 'json',
        success: data => {
          context.commit('setReceived', true);
          context.commit('initCart', data);
          context.commit('setLoaded', true);
        },
        error: () => {
          context.commit('setReceived', true);
          context.commit('logErrors', 'Ошибка инициализации корзины');
          context.commit('setInitialized', false);
        }
      });
    }
  },
  showCart(context) {
    context.commit('setCartState', true);
  },
  hideCart(context) {
    context.commit('setCartState', false);
  },
  addCartsProduct(context, id) {
    context.commit('setReceived', false);
    $.ajax({
      type: 'POST',
      url: '/api/cart/' + id.toString(),
      data: null,
      dataType: 'json',
      success: data => {
        context.commit('setReceived', true);
        context.commit('pushCartsProduct', data);
        context.commit('setCartState', true);
      },
      error: () => {
        context.commit('setReceived', true);
        context.commit('logErrors', 'Ошибка при добавлении товара в корзину');
      }
    })
  },
  deleteCartsProduct(context, id) {
    context.commit('setReceived', false);
    let product = context.getters.getProduct(id);
    let index = context.state.items.indexOf(product);
    $.ajax({
      type: 'POST',
      url: '/api/cart/' + id.toString(),
      data: {
        _method: 'DELETE'
      },
      dataType: 'json',
      success: data => {
        context.commit('setReceived', true);
        context.commit('setCartState', false);
        context.commit('removeCartsProduct', index);
      },
      error: () => {
        context.commit('setReceived', true);
        context.commit('logErrors', 'Ошибка при удалнии товара из корзины');
      }
    });
  },
  updateCartsProduct(context, {id, url = undefined}) {
    context.commit('setReceived', false);
    let product = context.getters.getProduct(id);
    $.ajax({
      type: 'POST',
      url: '/api/cart/' + id,
      data: {
        _method: 'PUT',
        quantity: product.quantity
      },
      dataType: 'json',
      success: data => {
        context.commit('setReceived', true);
        product.changed = false;
        context.commit('setCartState', false);
        if (url !== undefined) {
          window.location.href = url;
        }
      },
      error: () => {
        context.commit('setReceived', true);
        context.commit('logErrors', 'Ошибка при обновлении данных товара из корзины');
      }
    });
  },
  updateChanged(context, {product, method}) {
    if (context.getters.getReceived) {

      context.commit('setReceived', false);
      let quantity = product.quantity;
      if (method === 'decrementProductQuantity') {
        quantity--;
      } else {
        quantity++;
      }
      if (quantity > 0) {
        $.ajax({
          type: 'POST',
          url: '/api/cart/' + product.id,
          data: {
            _method: 'PUT',
            quantity: quantity
          },
          dataType: 'json',
          success(data) {
            context.commit('setReceived', true);
            context.commit(method, product);
            product.changed = false;
          },
          error() {
            context.commit('setReceived', true);
            context.commit('logErrors', 'Ошибка при обновлении данных товара из корзины');
          }
        });
      }

    }
  },
  incrementQuantity(context, id) {
    let product = context.getters.getProduct(id);
    if (context.getters.getListMode) {
      context.dispatch('updateChanged', {product, method: 'incrementProductQuantity'});
    } else {
      context.commit('incrementProductQuantity', product);
    }
  },
  decrementQuantity(context, id) {
    let product = context.getters.getProduct(id);
    if (context.getters.getListMode) {
      context.dispatch('updateChanged', {product, method: 'decrementProductQuantity'});
    } else {
      context.commit('decrementProductQuantity', product);
    }
  },
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}
