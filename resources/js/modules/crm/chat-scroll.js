const scrollChatToBottom = () => {
    const messages = document.querySelector('[data-crm-messages]');

    if (!messages) {
        return;
    }

    requestAnimationFrame(() => {
        messages.scrollTop = messages.scrollHeight;
    });
};

document.addEventListener('livewire:init', () => {
    window.Livewire?.on('crm-scroll-bottom', scrollChatToBottom);

    window.Livewire?.hook('morph.updated', ({ el }) => {
        if (el?.querySelector?.('[data-crm-messages]') || el?.matches?.('[data-crm-messages]')) {
            scrollChatToBottom();
        }
    });
});

document.addEventListener('DOMContentLoaded', scrollChatToBottom);
