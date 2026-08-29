import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import InfoFaqAccordion from '@/modules/info/components/InfoFaqAccordion.vue'
import { i18n } from '@/modules/locale'

describe('InfoFaqAccordion', () => {
  it('opens answer on question click', async () => {
    const wrapper = mount(InfoFaqAccordion, {
      global: {
        plugins: [i18n],
      },
      props: {
        title: 'FAQ',
        items: [
          { question: 'Question 1', answer: 'Answer 1' },
          { question: 'Question 2', answer: 'Answer 2' },
        ],
      },
    })

    expect(wrapper.find('.info-faq__answer').text()).toBe('Answer 1')

    const triggers = wrapper.findAll('.info-faq__trigger')
    await triggers[1].trigger('click')

    expect(wrapper.find('.info-faq__answer').text()).toBe('Answer 2')
  })
})
