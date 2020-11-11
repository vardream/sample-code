import './chooser_cosmetics';

module.exports = () => {
  /** @todo Проверить секцию video_block */
  let video_block = $('.videoblock'),
    video_block_exists = false,
    video_splash = null,
    video_strip = null;

  if (video_block !== undefined && video_block.length !== 0) {
    video_splash = $('.videosplash');
    if (video_splash !== undefined && video_splash.length !== 0) {
      video_strip = $('.videostrip');
      if (video_strip !== undefined && video_strip.length !== 0) {
        video_block_exists = true;
      }
    }
  }

  if (video_block_exists) {
    video_splash.first().bind(
      'click', event => {
        $(event.currentTarget).first().hide();
        video_strip.first().css('display', 'block');
      }
    );
  }
  // eof Проверить секцию video_block

  let beauty__container = $('.beauty__container'),
    beauty__container_exists = beauty__container !== undefined ? beauty__container.length !== 0 : false;

  if (beauty__container_exists) {

    beauty__container.find('.beauty__title').click(event => {
      $(event.currentTarget).hide();
      $(event.currentTarget).parent().find('.beauty__sections').show();
    });

    beauty__container.find('.beauty__sections > p').click(event => {
      $(event.currentTarget).parent().hide();
      $(event.currentTarget).parent().parent().find('.beauty__title').show();
    });

  }

  let prizes_btn = $('.prizes .btn'),
    prizes_btn_exsists = prizes_btn !== undefined ? prizes_btn.length > 0 : false;

  if (prizes_btn_exsists) {
    let prizes_block = $('.prizes_block'),
      prizes_block_exsists = prizes_block !== undefined ? prizes_block.length > 0 : false;
    if (prizes_block_exsists) {
      prizes_btn.click(event => {
        $(event.currentTarget).hide();
        $('.prizes_block').show();
      });
      prizes_block.click(event => {
        $(event.currentTarget).hide();
        $('.prizes .btn').show();
      });
    }
  }


  /** @todo Проверить секцию action__block_birthday */
  let action__block_birthday = $('.action__block .birthday');
  let action__block_birthday_exits = action__block_birthday !== undefined ? action__block_birthday.length !== 0 : false;

  if (action__block_birthday_exits) {
    action__block_birthday.each(item => {
      $(item).click(event => {
        $(event.currentTarget).next('.birthday_section').toggle();
      });
    });
  }
  // eof Проверить секцию action__block_birthday

  let questions_answers = $('.goods_item .question_sign'),
    questions_answers_exists = questions_answers !== undefined ? questions_answers.length !== 0 : false;

  if (questions_answers_exists) {
    questions_answers.click(event => {
      $(event.currentTarget).parent().find('.question_block .question_answer').toggle();
    });
    $(questions_answers).parent().find('.question_block .question_text').click(event => {
      $(event.currentTarget).parent().find('.question_answer').toggle();
    });
  }

  let chooser_wrapper = $('.chooser_wrapper'),
    chooser_wrapper_exists = chooser_wrapper !== undefined ? chooser_wrapper.length !== 0 : false;

  if (chooser_wrapper_exists) {
    chooser_wrapper.chooser_cosmetics();
  }

};
