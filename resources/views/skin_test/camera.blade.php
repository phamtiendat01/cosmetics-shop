@extends('layouts.app')
@section('title','Camera — Quét da AI')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6" x-data="cameraPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">Chụp ảnh khuôn mặt (tối đa 3)</h2>
        <div class="flex gap-2">
            <button class="px-3 py-2 rounded-md border hover:bg-gray-50" @click="switchCamera()">
                <i class="fa-solid fa-camera-rotate"></i> Đổi camera
            </button>
            <button class="px-3 py-2 rounded-md border hover:bg-gray-50" @click="toggleMirror()" x-text="mirror ? 'Tắt gương' : 'Bật gương'"></button>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="rounded-2xl overflow-hidden ring-1 ring-gray-200 relative bg-black">
            <video id="v" playsinline autoplay muted :class="mirror ? 'scale-x-[-1]' : ''"
                class="w-full h-[420px] object-cover bg-black"></video>
            <div class="absolute bottom-3 inset-x-0 flex items-center justify-center gap-3">
                <button class="px-4 py-2 rounded-full bg-white/90 hover:bg-white shadow"
                    :class="photos.length>=3 && 'pointer-events-none opacity-50'"
                    @click="capture()"><i class="fa-solid fa-camera"></i> Chụp</button>
                <button class="px-4 py-2 rounded-full bg-brand-600 text-white hover:bg-brand-700 shadow"
                    :class="photos.length===0 && 'pointer-events-none opacity-50'"
                    @click="done()"><i class="fa-solid fa-check"></i> Xong</button>
            </div>
        </div>

        <div>
            <div class="text-sm text-gray-600 mb-2">Ảnh đã chụp: <b x-text="photos.length"></b>/3</div>
            <div class="grid grid-cols-3 gap-3">
                <template x-for="(p,i) in photos" :key="i">
                    <div class="relative aspect-square rounded-xl overflow-hidden ring-1 ring-gray-200">
                        <img :src="p" class="w-full h-full object-cover">
                        <button class="absolute top-2 right-2 bg-white/90 hover:bg-white rounded-full p-1 shadow" @click="remove(i)">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </template>
            </div>
            <p class="text-xs text-gray-500 mt-3">Mẹo: ánh sáng tự nhiên, không filter/đeo kính…</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function cameraPage() {
        let stream = null,
            curDeviceIndex = 0,
            devices = [];
        return {
            photos: [],
            mirror: true,

            async init() {
                await this.startStream();
                await this.listCameras();
                window.addEventListener('beforeunload', () => this.stopStream());
            },

            async listCameras() {
                try {
                    const all = await navigator.mediaDevices.enumerateDevices();
                    devices = all.filter(d => d.kind === 'videoinput');
                } catch {}
            },

            async startStream(opts = {}) {
                try {
                    this.stopStream();
                    const vEl = document.getElementById('v');
                    const constraints = {
                        audio: false,
                        video: opts.deviceId ? {
                            deviceId: {
                                exact: opts.deviceId
                            }
                        } : (opts.facing ? {
                            facingMode: {
                                exact: opts.facing
                            }
                        } : {
                            facingMode: 'user'
                        })
                    };
                    stream = await navigator.mediaDevices.getUserMedia(constraints);
                    vEl.srcObject = stream;
                } catch (e) {
                    // Fallback facingMode
                    if (opts.deviceId) {
                        await this.startStream({
                            facing: this.mirror ? 'user' : 'environment'
                        });
                        return;
                    }
                    console.error(e);
                    alert('Không truy cập được camera. Hãy kiểm tra quyền camera của trình duyệt.');
                }
            },

            stopStream() {
                try {
                    stream?.getTracks()?.forEach(t => t.stop());
                } catch {}
            },

            async switchCamera() {
                if (devices.length > 1) {
                    curDeviceIndex = (curDeviceIndex + 1) % devices.length;
                    await this.startStream({
                        deviceId: devices[curDeviceIndex].deviceId
                    });
                    this.mirror = /front|user/i.test(devices[curDeviceIndex].label) || curDeviceIndex === 0;
                } else {
                    this.mirror = !this.mirror;
                    await this.startStream({
                        facing: this.mirror ? 'user' : 'environment'
                    });
                }
            },

            toggleMirror() {
                this.mirror = !this.mirror;
            },

            capture() {
                if (this.photos.length >= 3) return;
                const v = document.getElementById('v');
                const c = document.createElement('canvas');
                const w = v.videoWidth,
                    h = v.videoHeight;
                c.width = w;
                c.height = h;
                const ctx = c.getContext('2d');
                if (this.mirror) {
                    ctx.translate(w, 0);
                    ctx.scale(-1, 1);
                }
                ctx.drawImage(v, 0, 0, w, h);
                const dataUrl = c.toDataURL('image/jpeg', 0.92);
                this.photos.push(dataUrl);
            },

            remove(i) {
                this.photos.splice(i, 1);
            },

            done() {
                if (this.photos.length === 0) {
                    alert('Hãy chụp ít nhất 1 ảnh.');
                    return;
                }
                localStorage.setItem('skin_photos', JSON.stringify(this.photos.slice(0, 3)));
                this.stopStream();
                window.location = @json(route('skintest.index'));
            }
        }
    }
</script>
@endpush