<template>
  <div id="distributors-registration">

    <div v-if="state === 'default'" class="registration__info">
      <p @click="beginRegistration">Регистрация</p>
    </div>

    <div v-if="state === 'form'" class="registration__form">
      <h1>Регистрация дистрибьютора</h1>
      <p>Стать дистрибьютором очень просто. Заполните форму и мы свяжемся с Вами в ближайшее время.</p>

      <form v-on:submit.prevent="Registration" method="post" enctype="application/x-www-form-urlencoded">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
          <tbody>
          <tr>
            <td>
              <label for="person">*Имя:</label>
            </td>
            <td>
              <input name="person" id="person" type="text" placeholder="Ваше имя"
                     @change="ParseSpace"
                     data-vv-as="Имя"
                     v-validate="'required|person|max:64'"
                     v-model.trim="values.person">
              <span v-show="errors.has('person')">{{ errors.first('person') }}</span>
            </td>
          </tr>
          <tr>
            <td>
              <label for="phone">*Телефон:</label>
            </td>
            <td>
              <input name="phone" id="phone" type="tel" placeholder="телефонный номер"
                     @change="ParseSpace"
                     data-vv-as="Телефон"
                     v-validate="'required|phone|max:32'"
                     v-model.trim="values.phone">
              <span v-show="errors.has('phone')">{{ errors.first('phone') }}</span>
            </td>
          </tr>
          <tr>
            <td>
              <label for="email">*E-mail:</label>
            </td>
            <td>
              <input name="email" id="email" type="email" placeholder="электронная почта"
                     @change="ParseSpace"
                     data-vv-as="E-mail"
                     v-validate="'required|email|max:64'"
                     v-model.trim="values.email">
              <span v-show="errors.has('email')">{{ errors.first('email') }}</span>
            </td>
          </tr>
          <tr>
            <td>
              <label for="location">Местонахождение:</label>
            </td>
            <td>
              <input name="location" id="location" type="text" maxlength="248" placeholder="Ваш адрес"
                     @change="ParseSpace"
                     data-vv-as="Местонахождение"
                     v-validate="'max:248'"
                     v-model.trim="values.location">
              <span v-show="errors.has('location')">{{ errors.first('location') }}</span>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <button type="submit" :disabled="button_disabled" name="registration" value="send">Отправить</button>
            </td>
          </tr>
          </tbody>
        </table>
      </form>

    </div>

    <div v-if="state === 'success'" @click="ResetToDefault" class="registration__success">
      <h1>Заявка на регистрацию отправлена</h1>
      <p>Ответ будет отправлен на указанный Вами e-mail: <strong>{{ values.email }}</strong></p>
      <p>Спасибо за проявленый инетерс к продукции компании.</p>
    </div>

    <div v-if="state === 'error'" @click="ResetToDefault" class="registration__error">
      <h1>При отправке заявки произошла ошибка</h1>
      <p>Пожалуйста, попробуйте отправить заявку позже <br> или отправьте сообщение на наш email: info@mirra.biz.ua</p>
      <p>Спасибо за проявленый инетерс к продукции компании.</p>
    </div>

  </div>

</template>

<script>
  export default {
    name: "DistributorsRegistration",
    data() {
      return {
        state: "default",
        button_disabled: false,
        values: {
          person: "",
          phone: "",
          email: "",
          location: "",

        },
      }
    },
    methods: {
      beginRegistration() {
        this.state = "form"
      },
      Registration() {
        this.$validator.validateAll().then(result => {

          if (result) {
            // console.log('Validate Success!');

            this.button_disabled = true;

            $.ajax({
              url: '/api/registration',
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
                // console.log(jqXHR);
                // console.log(textStatus);
                // console.log(errorThrown);
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
          case "phone":
            // Замена символов табуляции на пробелы
            this.values[field] = this.values[field].replace(/\t+/img, " ");
            // Удаление повторяющихся пробельных символов
            this.values[field] = this.values[field].replace(/(\s)\s+/img, "$1");
            break;
          case "email":
            // Удаление повторяющихся пробельных символов
            this.values[field] = this.values[field].replace(/\s+/img, "");
            break;
          case "location":
            // Замена пробельных символов на символ пробела
            this.values[field] = this.values[field].replace(/\s+/img, " ");
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
    }
  }
</script>

<style lang="scss" scoped>
  #distributors-registration {

    h1 {
      font-size: 20px;
      text-transform: uppercase;
    }

    h1, h2, h3 {
      line-height: 1.3em;
      color: #009182;
      padding: 0;
      margin: 0 0 8px;
      font-style: normal;
      font-weight: normal;
    }

    p {
      font-size: 14px;
      line-height: 1.2em;
    }

    .registration__info {
      text-align: center;

      p {
        display: inline-block;
        font-size: 18px;
        line-height: 2.0em;
        text-align: center;
        text-transform: uppercase;
        padding: 0 2.0em;
        color: #e6e6e6;
        background-color: rgb(15, 125, 62);
        border-radius: 4px;
        margin: 0.5em 0;
        cursor: pointer;

        &:hover {
          background-color: darken(rgb(15, 125, 62), 5%);
        }
      }
    }

    .registration__form {
      /*border: 1px solid #e6e6e6;*/
      border-radius: 4px;
      margin: 1em 0;
      padding: 8px;
      box-shadow: 0 0 2px rgba(0, 0, 0, 0.4);

      form {
        margin: 1.0em 0;

        td:first-child {
          width: 25%;
        }

        td[colspan] {
          width: 100%;
          text-align: center;
        }

        input {
          line-height: 1em;
          box-sizing: border-box;
          width: 100%;
          height: 2em;
          margin: 8px 0;
          border: 1px solid #4d4d4d;
        }

        button {
          color: #fff;
          background-color: #348606;
          padding: 0.5em 1.5em;
          border: none;
          border-radius: 4px;

          &:hover {
            background-color: darken(#348606, 5%);
          }

          &:active {
            background-color: darken(#348606, 10%);
          }
        }
      }

    }

    .registration__success, .registration__error {
      border-radius: 4px;
      margin: 1em 0;
      padding: 8px;
      box-shadow: 1px 1px 2px rgba(0, 0, 0, 0.4);
      cursor: pointer;
    }

    .registration__success {
      border: 1px solid #348606;

      h1 {
        color: #348606;
      }
    }

    .registration__error {
      border: 1px solid rgb(153, 0, 0);

      h1 {
        color: rgb(153, 0, 0);
      }
    }
  }

</style>
