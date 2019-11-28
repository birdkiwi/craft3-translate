import Vue from 'vue';
import { EventBus } from './EventBus.js';
import SaveTranslations from './components/SaveTranslations.vue';
import TranslationsList from './components/TranslationsList.vue';

Vue.prototype.$csrfTokenName = window.csrfTokenName;
Vue.prototype.$csrfTokenValue = window.csrfTokenValue;
Vue.prototype.$craft = window.Craft;

new Vue({
  el: '#main',
  components: {
    SaveTranslations,
    TranslationsList
  },
  mounted () {
    EventBus.$on('translations-saved', () => {
      this.$craft.cp.displayNotice(this.$craft.t('app', 'Translations saved'));
    });
  }
});