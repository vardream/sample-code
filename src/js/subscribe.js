"use strict";

let visible = false,
  response_data = null,
  el_subscribe_message = null;

/**
 * Валидация формы
 * @param item
 * @returns {boolean}
 */
function validateSubscribeForm(item) {
  let data = $(item).find('input[type=email]').val();
  let regexp = /^[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/im;
  return regexp.test(data);
}

/**
 * Сокрытие и отображение формы
 * @param item
 * @param el__subscribe__button
 * @param visible
 */
function stateSubscribeFrom(item, el__subscribe__button, visible) {

  if (visible) {
    el__subscribe__button.addClass('subscribe__displayed');
    el__subscribe__button.find('> i.fa').removeClass('fa-caret-down').addClass('fa-caret-up');
    item.fadeIn(300);
  } else {
    el__subscribe__button.removeClass('subscribe__displayed');
    el__subscribe__button.find('> i.fa').removeClass('fa-caret-up').addClass('fa-caret-down');
    item.fadeOut(100);
  }
}

/**
 * Отображение окна сообщения
 */
function showResponseMessage() {
  el_subscribe_message = $(response_data);
  el_subscribe_message.click(hideSubscribeMessage);
  $('body').append(el_subscribe_message);
  // console.log('showResponseMessage');
}

/**
 * Сокрытие и удаление окна сообщения
 */
function hideSubscribeMessage() {
  el_subscribe_message.hide();
  el_subscribe_message.off('click');
  el_subscribe_message.remove();
  // console.log('hideSubscribeMessage');
}


/**
 * Стили поля E-mail исходные
 * @type object {{color: null, border-color: null}}
 */
let css__subscriber_mail = {
  'color': null,
  'border-color': null
};

$(document).ready(() => {
  let el_subscribe__container = $('.subscribe__container'),
    el_subscribe__container_exists = el_subscribe__container !== undefined ? el_subscribe__container.length !== 0 : false;
  let el_subscribe__footer_form = $('.subscribe__footer_form'),
    el_subscribe__footer_form_exists = el_subscribe__footer_form !== undefined ? el_subscribe__footer_form.length !== 0 : false;

  if (el_subscribe__container_exists) {

    /* Форма */
    let el__subscribe__from = el_subscribe__container.find('form');

    /* Кнопка "Рассылка" */
    let el__subscribe__button = el_subscribe__container.find('.subscribe__button');

    /* Поле E-mail */
    let el__subscriber_mail = el__subscribe__from.find('input[type=email]');

    /* Кнопка "Подписаться" */
    let el__subscribe__submit_button = el_subscribe__container.find('button[type=submit]');

    css__subscriber_mail.color = el__subscriber_mail.css('color');
    css__subscriber_mail['border-color'] = el__subscriber_mail.css('border-color');

    el__subscriber_mail.focus(event => {
      $(event.target).css({
        'color': css__subscriber_mail.color,
        'border-color': css__subscriber_mail['border-color']
      });
    });

    el_subscribe__container.find('form.subscribe__form').submit(event => {

      /*
      * 1. Проверка e-mail:
      * 1.1. Успех:
      * 1.1.1. Отправка данных формы
      * 1.1.1.1. Успех: скрыть форму
      *
      * 1.2. Ошибка:
      * 1.2.1. Установить красную рамочку для поля с ошибкой
      * */

      event.preventDefault();

      el__subscribe__submit_button.attr('disabled', 'disabled');

      if (validateSubscribeForm(event.currentTarget)) {

        // 1.1.1. Отправка данных формы
        // console.log('Форма заполнена правильно');

        let el_subscribe_form = $(event.currentTarget);
        let data_to_send = el_subscribe_form.serialize();

        // Отправка данных формы
        $.ajax({
          url: '/api/subscribe',
          type: 'POST',
          data: data_to_send,
          dataType: 'html',
          // успех
          success: (data, textStatus) => {
            el__subscribe__from[0].reset();
            response_data = data;
            showResponseMessage();

            // console.log(textStatus);
            // console.log(response_data);
          },
          // ошибка
          error: (jqXHR, textStatus, errorThrown) => {
            response_data = null;

            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
          }
        });

        visible = false;
      } else {
        // console.log("Ошибка в поле e-mail.");
        el__subscriber_mail.css({
          'border-color': 'red',
          'color': 'red'
        });
      }

      el__subscribe__submit_button.removeAttr('disabled');
      stateSubscribeFrom($(event.target), el__subscribe__button, visible);

    });

    el__subscribe__button.click(event => {
      visible = !visible;
      stateSubscribeFrom(el__subscribe__from, $(event.target), visible);
    });

  }

  if (el_subscribe__footer_form_exists) {

    /* Поле E-mail */
    // let el__subscriber_mail = el_subscribe__footer_form.find('input[type=email]');

    /* Кнопка "Подписаться" */
    let el__subscribe__footer_submit_button = el_subscribe__footer_form.find('button[type=submit]');


    el_subscribe__footer_form.submit(event => {

      event.preventDefault();

      el__subscribe__footer_submit_button.attr('disabled', 'disabled');

      if (validateSubscribeForm(event.currentTarget)) {

        // 1.1.1. Отправка данных формы
        console.log('Форма заполнена правильно');

        let el_subscribe_footer_form = $(event.currentTarget);
        let footer_data_to_send = el_subscribe_footer_form.serialize();

        console.log(footer_data_to_send);

        // Отправка данных формы
        $.ajax({
          url: '/api/subscribe',
          type: 'POST',
          data: footer_data_to_send,
          dataType: 'html',
          // успех
          success: (data, textStatus) => {
            el_subscribe__footer_form[0].reset();
            response_data = data;
            showResponseMessage();

            console.log(textStatus);
            console.log(response_data);
          },
          // ошибка
          error: (jqXHR, textStatus, errorThrown) => {
            response_data = null;

            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
          }
        });

        el__subscribe__footer_submit_button.removeAttr('disabled');
      }
    });

  }
});
