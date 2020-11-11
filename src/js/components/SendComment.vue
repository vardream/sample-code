<template>
  <div id="send-comment">

    <div v-if="state === 'default'" class="comment__info">
      <p @click="beginComment">Написать {{ texts[contentType][0] }}</p>
    </div>

    <div v-if="state === 'form'" class="comment__form">
      <h1>{{ texts[contentType][1] }}</h1>
      <form v-on:submit.prevent="sendComment" method="post" enctype="application/x-www-form-urlencoded">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
          <tr>
            <td>
              <label for="person">*Имя:</label>
            </td>
            <td>
              <input name="person" id="person" type="text" tabindex="1" placeholder="Ваше имя"
                     @change="ParseSpace"
                     data-vv-as="Имя"
                     v-validate="'required|person|max:64'"
                     v-model.trim="values.person">
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
              <input name="email" id="email" type="email" tabindex="3" placeholder="Электронная почта"
                     @change="ParseSpace"
                     data-vv-as="E-mail"
                     v-validate="'required|email|max:64'"
                     v-model.trim="values.email">
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
              <label for="message">*{{ texts[contentType][2] }}:</label>
            </td>
            <td></td>
          </tr>
          <tr>
            <td colspan="2">
            <textarea name="message" id="message" tabindex="4" rows="16" :placeholder="texts[contentType][3]"
                      @change="ParseSpace"
                      :data-vv-as="texts[contentType][2]"
                      v-validate="'required|max:1024'"
                      v-model.trim="values.message">
            </textarea>
            </td>
          </tr>
          <tr v-show="errors.has('message')">
            <td colspan="2">
              <span>{{ errors.first('message') }}</span>
            </td>
          </tr>
          <tr>
            <td colspan="2" style="text-align: center">
              <button type="submit" :disabled="button_disabled" tabindex="5" value="send">Отправить</button>
            </td>
          </tr>
        </table>
      </form>
    </div>

    <div v-if="state === 'success'" @click="ResetToDefault" class="comment__success">
      <h1>Спасибо за Ваш {{ texts[contentType][0]}}</h1>
      <p>После проверки, {{ texts[contentType][0]}} будет опубликован.</p>
      <p>Не будут опубликованы только {{ texts[contentType][4]}} не по теме, либо {{ texts[contentType][4]}}, содержащие
        не нормативную лексику.</p>
      <p>Спасибо за проявленый инетерс к продукции компании.</p>
    </div>

    <div v-if="state === 'error'" @click="ResetToDefault" class="comment__error">
      <h1>При отправке {{ texts[contentType][5]}} произошла ошибка</h1>
      <p>Пожалуйста, попробуйте позже <br> или отправьте сообщение на наш email: info@mirra.biz.ua</p>
      <p>Спасибо за проявленый инетерс к продукции компании.</p>
    </div>

  </div>
</template>

<script>
  export default {
    name: "send-comment",
    props: {
      contentType: {
        type: String,
        default: ""
      },
      contentItem: {
        type: Number,
        default: 0
      }
    },
    data() {
      return {
        state: "default",
        button_disabled: false,
        texts: {
          product: [
            "отзыв",
            "Отзыв о продукте",
            "Отзыв",
            "Ваш отзыв о продукте",
            "отзывы",
            "отзыва",

          ],
          article: [
            "комментарий",
            "Комментарий",
            "Комментарий",
            "Ваш комментарий",
            "комментарии",
            "комментария",

          ],
          post: [
            "комментарий",
            "Комментарий",
            "Комментарий",
            "Ваш комментарий",
            "комментарии",
            "комментария",

          ],
        },

        values: {
          person: "",
          email: "",
          message: "",

        }
      }

    },
    methods: {
      beginComment() {
        this.state = "form"
      },
      sendComment() {
        this.$validator.validateAll().then(result => {

          if (result) {
            console.log('Validate Success!');

            this.button_disabled = true;

            $.ajax({
              url: '/api/comment/' + this.contentType + '/' + this.contentItem,
              type: 'POST',
              data: this.values,
              dataType: 'html',
              // успех
              success: (data, textStatus) => {
                this.state = "success";
              },
              // ошибка
              error: (jqXHR, textStatus, errorThrown) => {
                this.state = "error";
              }
            });
          }
        });
      },
      ResetToDefault() {
        if (this.state === 'success') {
          for (let field in this.values) {
            this.values[field] = "";
          }
        }
        this.state = "default";
        this.button_disabled = false;
      },
      ParseSpace(event) {
        let field = event.target.name;

        switch (field) {
          case "person":
            // Замена символов табуляции на пробелы
            this.values[field] = this.values[field].replace(/\t+/img, " ");
            // Удаление повторяющихся пробельных символов
            this.values[field] = this.values[field].replace(/(\s)\s+/img, "$1");
            // Унификация апострофов
            this.values[field] = this.values[field].replace(/(\S)([\u0022\u0027\u0060]+)(\S)/img, "$1\u0027$3");
            break;
          case "email":
            // Удаление повторяющихся пробельных символов
            this.values[field] = this.values[field].replace(/\s+/img, "");
            break;
          case "message":
            // Замена символов табуляции на пробелы
            this.values[field] = this.values[field].replace(/\t+/img, " ");
            // Удаление повторяющихся пробельных символов
            this.values[field] = this.values[field].replace(/(\s)\s+/img, "$1");
            // Унификация апострофов
            this.values[field] = this.values[field].replace(/(\S)([\u0022\u0027\u0060]+)(\S)/img, "$1\u0027$3");
            // Удаление пробелов перед знаками препинания
            this.values[field] = this.values[field].replace(/[ ]([.,;:!?…]+)/img, "$1");
            // Расстановка троеточий
            this.values[field] = this.values[field].replace(/(\.{2,})/img, "…");
            // Добавление отсутсвующих пробелов после знаков препинания
            this.values[field] = this.values[field].replace(/([.,;:!?…]+)(\S)/img, "$1 $2");
            break;
        }

      }
    },

  }
</script>

<style>

</style>
