import { useEcho } from '@laravel/echo-vue';
import { defineComponent, type PropType } from 'vue';

export default defineComponent({
    name: 'EchoChannel',
    props: {
        channel: { type: String, required: true },
        events: { type: Array as PropType<string[]>, required: true },
    },
    emits: ['heard'],
    setup(props, { emit }) {
        props.events.forEach((event) => useEcho(props.channel, event, () => emit('heard', event)));

        return () => null;
    },
});
