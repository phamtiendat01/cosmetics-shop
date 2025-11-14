<div x-data="{items:[]}" @toast.window="items.push($event.detail)"
    class="fixed bottom-4 left-1/2 -translate-x-1/2 z-[90] space-y-2">
    <template x-for="(t,idx) in items" :key="idx">
        <div x-init="setTimeout(()=>items.splice(idx,1), 2200)"
            class="px-4 py-2 rounded-lg shadow-card text-white"
            :class="t.type==='error' ? 'bg-rose-600' : 'bg-emerald-600'">
            <span x-text="t.text || 'Thao tác thành công'"></span>
        </div>
    </template>
</div><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/shared/toast.blade.php ENDPATH**/ ?>