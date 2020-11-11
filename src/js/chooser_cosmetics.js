const chooser_data = {
  "questions": [
    {
      'used': false
    },
    {
      // "q": "Какой у Вас тип кожи?",
      "a": [
        {
          // "option": "Сухая",
          "result": "q1a1"
        },
        {
          // "option": "Нормальная",
          "result": "q1a2"
        },
        {
          // "option": "Жирная",
          "result": "q1a3"
        },
        {
          // "option": "Комбинированная",
          "result": "q1a4"
        }
      ],
      'used': true
    },
    {
      // "q": "Ваш возраст?",
      "a": [
        {
          // "option": "от 20 до 30",
          "result": "q2a1"
        },
        {
          // "option": "от 30 до 45",
          "result": "q2a2"
        },
        {
          // "option": "свыше 45",
          "result": "q2a3"
        }
      ],
      'used': true
    },
    {
      // "q": "Оцените упругость Вашей кожи, наличие морщинок:",
      "a": [
        {
          // "option": "Выглядит хорошо, морщин нет, кожа упругая",
          "result": "q3a1"
        },
        {
          // "option": "Небольшие морщинки в области глаз и/или у рта, между бровями, в области носа и губ",
          "result": "q3a2"
        },
        {
          // "option": "Выраженные проявления морщинок в области глаз и/или у рта, между бровями, в области носа и губ",
          "result": "q3a3"
        },
        {
          // "option": "Явно выраженные проявления морщинок в области глаз и/или у рта, между бровями, в области носа и губ",
          "result": "q3a4"
        }
      ],
      'used': false
    },
    {
      // "q": "Чувствительность. Бывает ли у Вас раздражение, покраснения и/или шелушения на коже?",
      "a": [
        {
          // "option": "Нет никогда",
          "result": "q4a1"
        },
        {
          // "option": "Очень редко/периодически",
          "result": "q4a2"
        },
        {
          // "option": "Постоянно",
          "result": "q4a3"
        }
      ],
      'used': false
    },
    {
      // "q": "Пигментация. Есть ли у Вас пигментные пятна, веснушки на коже?",
      "a": [
        {
          // "option": "Нет, кожа чистая",
          "result": "q5a1"
        },
        {
          // "option": "Есть, незначительное количество",
          "result": "q5a2"
        },
        {
          // "option": "Да есть, выраженные и по всей коже лица",
          "result": "q5a3"
        }
      ],
      'used': false
    },
    {
      // "q": "Купероз. Есть ли у Вас проявления сосудистой сеточки на лице?",
      "a": [
        {
          // "option": "Нет",
          "result": "q6a1"
        },
        {
          // "option": "Незначительное",
          "result": "q6a2"
        },
        {
          // "option": "Да, явно выраженное",
          "result": "q6a3"
        }
      ],
      "used": false
    }
  ],

  "results": {
    "q1a1q2a1": "type-1-set-1",
    "q1a2q2a1": "type-1-set-2",
    "q1a3q2a1": "type-1-set-3",
    "q1a4q2a1": "type-1-set-4",
    "q1a1q2a2": "type-2-set-1",
    "q1a2q2a2": "type-2-set-2",
    "q1a3q2a2": "type-2-set-3",
    "q1a4q2a2": "type-2-set-4",
    "q1a1q2a3": "type-3-set-1",
    "q1a2q2a3": "type-3-set-2",
    "q1a3q2a3": "type-3-set-3",
    "q1a4q2a3": "type-3-set-4"
  }

};
(function ($) {
  $.chooser_cosmetics = function (element, options) {
    var plugin = this,
      $element = $(element),

      question_blocks = null,
      question_current = 0,
      chooser_result = "",

      defaults = {
        class_block: "chooser_block",
        class_body: "chooser_body",
        class_item: "chooser_item",
        class_question: "chooser_question",
        fadeInValue: 250
      };

    plugin.config = $.extend(defaults, options);

    var blocks_data = (plugin.config.json ? plugin.config.json : typeof chooser_data !== 'undefined' ? chooser_data : null);

    plugin.method = {

      selectItems: function (el) {
        var items;
        items = $(el).find('.' + plugin.config.class_body + ' .' + plugin.config.class_item);
        if (items.length === 0) {
          items = $(el);
        }
        return items;
      },

      addEvents: function (el) {
        var i = 0;
        var items = this.selectItems(el);
        var answer = '';
        var used = false;
        if (blocks_data.questions.hasOwnProperty(question_current)) {
          var list = blocks_data.questions[question_current];
        }
        while (i < items.length) {
          if (list !== 'undefined' && list.hasOwnProperty('used') && list.hasOwnProperty('a') && list.a.hasOwnProperty(i)) {
            answer = list.a[i].hasOwnProperty('result') ? list.a[i].result : '';
            used = list.used;
          }

          $(items[i]).on('click', {result: answer, used: used}, this.showNext);
          i++;
        }
      },

      removeEvents: function (el) {
        var i = 0;
        var items = this.selectItems(el);
        while (i < items.length) {
          $(items[i]).off('click', this.showNext);
          i++;
        }
      },

      showNext: function (event) {
        if (event.data.used) {
          chooser_result += event.data.result;
        }
        plugin.method.removeEvents(question_blocks[question_current]);
        if (question_current + 1 < question_blocks.length) {
          $(question_blocks[question_current]).hide();
          question_current++;
          plugin.method.addEvents(question_blocks[question_current]);
          // $(question_blocks[question_current]).show();
          $(question_blocks[question_current]).fadeIn(plugin.config.fadeInValue);
        } else {
          plugin.method.showResult();
        }
      },

      showResult: function () {
        var link = "chooser";
        if (blocks_data.results.hasOwnProperty(chooser_result)) {
          link += '/' + blocks_data.results[chooser_result];
        }
        window.location.href = "/" + link;
      }
    };

    plugin.init = function () {
      var i = 0;
      question_blocks = $($element).find('.' + plugin.config.class_block);
      question_current = 0;
      while (i < question_blocks.length) {
        if (i === question_current) {
          this.method.addEvents($(question_blocks[i]));
          $(question_blocks[i]).show();
        } else {
          $(question_blocks[i]).hide();
        }
        i++;
      }
    };

    plugin.init();
  };

  $.fn.chooser_cosmetics = function (options) {
    return this.each(function () {
      var plugin = new $.chooser_cosmetics(this, options);
      $(this).data("chooser_cosmetics", plugin);
    });
  };

})(jQuery);
