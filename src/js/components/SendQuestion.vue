<template>
  <div id="send-question">

    <div v-show="state === 'default'" class="question__form">

      <form v-on:submit.prevent="Question" method="post" enctype="application/x-www-form-urlencoded">
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
              <label for="subject">*Тема:</label>
            </td>
            <td>
              <input name="subject" id="subject" type="text" tabindex="2" placeholder="Тема вопроса"
                     @change="ParseSpace"
                     data-vv-as="Тема"
                     v-validate="'required|max:64'"
                     v-model.trim="values.subject">
            </td>
          </tr>
          <tr v-show="errors.has('subject')">
            <td></td>
            <td>
              <span>{{ errors.first('subject') }}</span>
            </td>
          </tr>
          <tr>
            <td>
              <label for="email">*E-mail:</label>
            </td>
            <td>
              <input name="email" id="email" type="email" tabindex="3" placeholder="электронная почта"
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
              <label for="message">*Вопрос:</label>
            </td>
            <td></td>
          </tr>
          <tr>
            <td colspan="2">
            <textarea name="message" id="message" tabindex="4" rows="16" placeholder="Ваше сообщение"
                      @change="ParseSpace"
                      data-vv-as="Вопрос"
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

    <div v-if="state === 'success'" @click="ResetToDefault" class="question__success">
      <h1>Ваш вопрос отправлен</h1>
      <p>Ответ будет отправлен на указанный Вами e-mail: <strong>{{ values.email }}</strong></p>
      <p>Спасибо за проявленый инетерс к продукции компании.</p>
    </div>

    <div v-if="state === 'error'" @click="ResetToDefault" class="question__error">
      <h1>При отправке вопроса произошла ошибка</h1>
      <p>Пожалуйста, попробуйте отправить вопрос позже <br> или отправьте сообщение на наш email: info@mirra.biz.ua</p>
      <p>Спасибо за проявленый инетерс к продукции компании.</p>
    </div>

  </div>
</template>

<script>
  export default {
    name: "send-question",
    data() {
      return {
        state: "default",
        button_disabled: false,
        values: {
          person: "",
          subject: "",
          email: "",
          message: "",

        }
      }
    },
    methods: {
      Question() {
        this.$validator.validateAll().then(result => {

          if (result) {
            // console.log('Validate Success!');

            this.button_disabled = true;

            $.ajax({
              url: '/api/question',
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
          case "subject":
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

    }
  }
</script>

<style lang="scss" scoped>
  #send-question {

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

    .question__form {
      border-radius: 4px;
      margin: 1em 0;
      padding: 8px;
      box-shadow: 0 0 2px rgba(0, 0, 0, 0.4);

      form {
        margin: 1.0em 0;

        td {
          vertical-align: middle;

          label {
            line-height: 2.5em;
          }
        }

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

        textarea {
          box-sizing: border-box;
          min-width: 100%;
          max-width: 502px;
          min-height: 3em;
          font-family: inherit;
          font-size: inherit;
          line-height: 1.5em;
          margin: 8px 0;

        }

        button {
          color: #fff;
          background-color: #348606;
          padding: 0.5em 1.5em;
          border: none;
          border-radius: 4px;
          margin: 8px 0;

          &:hover {
            background-color: darken(#348606, 5%);
          }

          &:active {
            background-color: darken(#348606, 10%);
          }
        }
      }

    }

    .question__success, .question__error {
      border-radius: 4px;
      margin: 1em 0;
      padding: 8px;
      box-shadow: 1px 1px 2px rgba(0, 0, 0, 0.4);
      cursor: pointer;
    }

    .question__success {
      border: 1px solid #348606;

      h1 {
        color: #348606;
      }
    }

    .question__error {
      border: 1px solid rgb(153, 0, 0);

      h1 {
        color: rgb(153, 0, 0);
      }
    }
  }
</style>
