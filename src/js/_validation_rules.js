import Vue from 'vue/dist/vue.esm';
import VeeValidate, { Validator } from 'vee-validate';
import ru from 'vee-validate/dist/locale/ru';

Vue.use(VeeValidate);

Validator.localize('ru', ru);

const validatePerson = {
  getMessage(field, args) {
    return `${field} должно быть набрано кирилицей без сокращений`
  },
  validate(value, args) {
    return /^([\u0401\u0404\u0406\u0407\u0451\u0454\u0456\u0457\u0490\u0491\u0410-\u044F]+([\-\u0022\u0027\u0060\u2019]?[\u0401\u0404\u0406\u0407\u0451\u0454\u0456\u0457\u0490\u0491\u0410-\u044F])+\s*)+$/im.test(value);
  }
};
const validatePhone = {
  getMessage(field, args) {
    return `Поле ${field} должно быть корректным телефонным номером.`;
  },
  validate(value, args) {
    return value === '' ? true : /^\+?\d+[\s\-.]*(\(\d+\)|\d+)[\s\-.]*(\d+[\s\-.]*)+$/im.test(value);
  }
};

Validator.extend('person', validatePerson);
Validator.extend('phone', validatePhone);

