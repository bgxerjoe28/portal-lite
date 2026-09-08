<template>
    <Head :title="'Ujian: ' + exam.title" />
    <Toast />

    <!-- 1. PERMANENT BLOCK OVERLAY -->
    <div v-if="isBlocked" class="fixed top-0 left-0 right-0 bottom-0 overflow-y-auto p-3 text-center" style="z-index: 99999; background-color: #090d16;">
        <div class="min-h-full flex flex-column align-items-center justify-content-center">
            <div class="w-full max-w-lg border-round-2xl border shadow-8 p-4 md:p-6 flex flex-column align-items-center gap-3 m-auto" style="background-color: #0f172a; border-color: #ef4444;">
                <div class="border-circle p-3 flex align-items-center justify-content-center border" style="width: 70px; height: 70px; background-color: rgba(69, 10, 10, 0.4); color: #ef4444; border-color: #ef4444;">
                    <i class="pi pi-ban text-3xl font-bold"></i>
                </div>
                <h2 class="text-xl md:text-2xl font-bold text-white m-0">Akses Ujian Diblokir!</h2>
                <p class="text-sm md:text-base line-height-3 m-0" style="color: #cbd5e1;">
                    Anda terdeteksi melakukan pelanggaran fokus (meninggalkan layar ujian) sebanyak <strong>{{ warningCount }} kali</strong>.
                </p>
                <div class="p-3 border-round-xl border text-xs md:text-sm w-full" style="background-color: rgba(69, 10, 10, 0.2); border-color: #ef4444; color: #f87171;">
                    Sesi ujian Anda telah diblokir secara permanen untuk mata uji ini. Silakan hubungi proktor/pengawas ujian di ruangan Anda.
                </div>
            </div>
        </div>
    </div>

    <!-- 2. SUSPENDED OVERLAY -->
    <div v-else-if="isSuspended" class="fixed top-0 left-0 right-0 bottom-0 overflow-y-auto p-3 text-center" style="z-index: 99999; background-color: #090d16;">
        <div class="min-h-full flex flex-column align-items-center justify-content-center py-4">
            <div class="w-full max-w-lg border-round-2xl border shadow-8 p-4 md:p-6 flex flex-column align-items-center gap-3 m-auto" style="background-color: #0f172a; border-color: #f59e0b;">
                <div class="border-circle p-3 flex align-items-center justify-content-center border" style="width: 70px; height: 70px; background-color: rgba(69, 26, 3, 0.4); color: #f59e0b; border-color: #f59e0b;">
                    <i class="pi pi-exclamation-triangle text-3xl font-bold"></i>
                </div>
                <h2 class="text-2xl md:text-3xl font-bold text-white m-0">Fokus Terputus!</h2>
                <div class="text-sm md:text-base line-height-3 m-0 p-3 border-round-xl w-full border" style="background-color: #1e293b; border-color: #334155; color: #e2e8f0;">
                    <p class="m-0 mb-2 font-semibold">Anda terdeteksi keluar dari layar pengerjaan ujian.</p>
                    <p class="m-0 font-bold" style="color: #fbbf24;">
                        Halaman dan soal dikunci sementara 
                        <span v-if="warningCount === 1">(Peringatan ke-1 — dikunci 1 menit)</span>
                        <span v-else-if="warningCount === 2">(Peringatan ke-2 — dikunci 5 menit)</span>
                        <span v-else>(Peringatan ke-{{ warningCount }})</span>
                    </p>
                </div>
                <div class="flex flex-column align-items-center gap-1 my-2">
                    <span class="text-sm md:text-base font-semibold" style="color: #cbd5e1;">Halaman akan terbuka kembali dalam:</span>
                    <span class="text-5xl md:text-6xl font-mono font-bold drop-shadow-md" style="color: #f59e0b;">{{ suspensionRemainingSeconds }}</span>
                    <span class="text-xs md:text-sm font-bold tracking-widest uppercase" style="color: #f59e0b;">Detik</span>
                </div>
                <div class="p-3 border-round-xl border text-xs md:text-sm line-height-3 text-left w-full" style="background-color: rgba(69, 26, 3, 0.3); border-color: #f59e0b; color: #fcd34d;">
                    <strong class="block mb-1" style="color: #f59e0b;"><i class="pi pi-info-circle mr-1"></i>ATURAN FOKUS:</strong>
                    Menutup halaman, beralih tab, menekan tombol Windows/Alt+Tab, membuka aplikasi lain, atau keluar dari Fullscreen dianggap sebagai pelanggaran fokus.
                    <br><br>
                    <span class="block">⚠️ Pelanggaran ke-<b>1</b>: Halaman dikunci <b>1 menit</b></span>
                    <span class="block">⚠️ Pelanggaran ke-<b>2</b>: Halaman dikunci <b>5 menit</b></span>
                    <span class="block">🚫 Pelanggaran ke-<b>3</b>: Dikeluarkan otomatis (perlu token ulang)</span>
                    <span class="block">🔒 Pelanggaran ke-<b>4</b>: Diblokir permanen &amp; ujian dikumpulkan paksa</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. FULLSCREEN ENFORCER OVERLAY -->
    <div v-else-if="!isFullscreenActive" class="fixed top-0 left-0 right-0 bottom-0 overflow-y-auto p-3 text-center" style="z-index: 99999; background-color: #090d16;">
        <div class="min-h-full flex flex-column align-items-center justify-content-center">
            <div class="w-full max-w-lg border-round-2xl border shadow-8 p-4 md:p-6 flex flex-column align-items-center gap-3 m-auto" style="background-color: #0f172a; border-color: #3b82f6;">
                <div class="border-circle p-3 flex align-items-center justify-content-center border" style="width: 70px; height: 70px; background-color: rgba(23, 37, 84, 0.4); color: #3b82f6; border-color: #3b82f6;">
                    <i class="pi pi-window-maximize text-3xl font-bold"></i>
                </div>
                <h2 class="text-xl md:text-2xl font-bold text-white m-0">Wajib Layar Penuh!</h2>
                <p class="text-sm md:text-base line-height-3 m-0" style="color: #cbd5e1;">
                    Ujian ini mewajibkan mode Layar Penuh (Fullscreen) untuk mencegah kecurangan.
                </p>
                <Button 
                    label="Aktifkan Layar Penuh" 
                    icon="pi pi-window-maximize"
                    class="font-bold p-3 border-round-xl w-full text-sm md:text-base"
                    style="background-color: #2563eb; color: #ffffff; border: none;"
                    @click="requestFullscreen"
                />
                <div class="text-xs mt-1" style="color: #94a3b8;">
                    Menolak atau mencoba keluar dari layar penuh akan dicatat sebagai pelanggaran fokus.
                </div>
            </div>
        </div>
    </div>

    <div class="min-h-screen flex flex-column bg-slate-900 text-slate-100 pb-8 select-none">
        <!-- 0. DRY RUN / SIMULASI GURU BANNER -->
        <div v-if="isDryRun" class="bg-amber-400 text-slate-950 px-4 py-2 flex flex-wrap justify-content-between align-items-center gap-2 font-bold shadow-4 sticky top-0" style="z-index: 1001;">
            <div class="flex align-items-center gap-2 text-sm md:text-base">
                <i class="pi pi-desktop text-xl text-slate-900"></i>
                <span>MODE SIMULASI CBT (DRY RUN GURU) — Ujian riil berjalan tanpa menyimpan data ke database.</span>
            </div>
            <div class="flex align-items-center gap-2">
                <Button 
                    label="Keluar dari Simulasi" 
                    icon="pi pi-sign-out" 
                    severity="danger" 
                    size="small" 
                    class="font-bold py-1 px-3 text-xs"
                    @click="exitDryRun" 
                />
            </div>
        </div>

        <!-- HEADER -->
        <header class="border-bottom-1 border-slate-700 py-3 px-4 flex justify-content-between align-items-center sticky top-0 shadow-3" style="z-index: 1000; background-color: #1e293b !important;">
            <div class="flex flex-column gap-1">
                <span class="font-bold text-base md:text-lg text-white truncate max-w-20rem md:max-w-none" style="color: #ffffff !important;">{{ exam.title }}</span>
                <div class="flex align-items-center gap-2 text-xs md:text-sm">
                    <span class="font-semibold" style="color: #cbd5e1 !important;">{{ exam.bank?.subject?.name || 'Ujian' }}</span>
                    <span style="color: #94a3b8 !important;">•</span>
                    <span class="font-bold" style="color: #34d399 !important;"><i class="pi pi-user mr-1 text-xs" style="color: #34d399 !important;"></i>{{ studentExam.student?.full_name || 'Peserta' }}</span>
                </div>
            </div>
            <!-- ACTIONS (MUAT ULANG, PETA SOAL & TIMER) -->
            <div class="flex align-items-center gap-2 md:gap-3">
                <Button 
                    class="bg-slate-950 border-slate-700 hover:bg-slate-700 text-white font-semibold border-round-xl" 
                    :loading="isReloadingQuestions"
                    @click="reloadQuestions"
                    style="color: #ffffff !important;"
                    v-tooltip.bottom="'Muat Ulang Soal'"
                >
                    <i v-if="!isReloadingQuestions" class="pi pi-sync"></i>
                    <span class="mobile-hide ml-1">Muat Ulang Soal</span>
                </Button>
                <Button 
                    class="bg-slate-950 border-slate-700 hover:bg-slate-700 text-white font-semibold border-round-xl" 
                    @click="displayMapModal = true"
                    style="color: #ffffff !important;"
                    v-tooltip.bottom="'Peta Soal'"
                >
                    <i class="pi pi-th-large"></i>
                    <span class="mobile-hide ml-1">Peta Soal</span>
                </Button>
                <div class="bg-slate-950 px-3 py-2 border-round-xl border border-slate-700 flex align-items-center gap-2" style="background-color: #020617 !important; border-color: #334155 !important;">
                    <i class="pi pi-clock font-bold" style="color: #f59e0b !important;"></i>
                    <span class="font-mono text-base md:text-lg font-bold" style="color: #fbbf24 !important;">{{ formattedRemainingTime }}</span>
                </div>
            </div>
        </header>

        <div class="grid grid-nogutter p-3 flex-1 gap-3 align-items-stretch">
            <!-- SOAL PANE (FULL WIDTH) -->
            <div class="col-12 flex flex-column gap-3">
                <div class="bg-slate-800 border border-slate-700 p-4 border-round-xl shadow-2 flex-1 flex flex-column" @click="handleImageClick">
                    <!-- Soal Header Info -->
                    <div class="flex justify-content-between align-items-center mb-4 border-bottom-1 border-slate-700 pb-2">
                        <span class="font-bold text-lg text-blue-400">Soal Nomor {{ currentIdx + 1 }} dari {{ questions.length }}</span>
                        <Tag 
                            v-if="answers[currentQuestion.id]?.is_doubtful" 
                            value="Ragu-Ragu" 
                            severity="warn" 
                            class="bg-amber-500/20 border border-amber-500 text-amber-400" 
                        />
                    </div>

                    <!-- Question Body -->
                    <div 
                        class="text-slate-200 text-base md:text-lg mb-6 line-height-3 flex-1" 
                        v-html="currentQuestion.question_text"
                        @change="handleDynamicChange"
                    ></div>

                    <!-- Options / Answer Input -->
                    <div class="mb-6">
                        <!-- 1. PILIHAN GANDA & 8. SURVEY & 9. SKOR BERBEDA -->
                        <div v-if="['pilihan_ganda', 'survey', 'skor_berbeda'].includes(currentQuestion.question_type)" class="flex flex-column gap-3">
                            <div 
                                v-for="(val, optKey, idx) in currentQuestion.options" 
                                :key="optKey"
                                class="option-card-btn flex align-items-center gap-3 p-3 cursor-pointer"
                                :class="answers[currentQuestion.id]?.selected_answer === optKey ? 'selected' : 'unselected'"
                                @click="selectSingleOption(optKey, $event)"
                            >
                                <span 
                                    class="option-badge-circle"
                                    :class="answers[currentQuestion.id]?.selected_answer === optKey ? 'selected' : 'unselected'"
                                >
                                    {{ String.fromCharCode(65 + idx) }}
                                </span>
                                <span class="text-sm md:text-base line-height-3" v-html="val"></span>
                            </div>
                        </div>

                        <!-- 2. ISIAN SINGKAT -->
                        <div v-else-if="currentQuestion.question_type === 'isian_singkat' && !hasDynamicInputs" class="p-fluid">
                            <label class="block text-sm text-slate-400 font-semibold mb-2">Tulis Jawaban Singkat Anda:</label>
                            <InputText 
                                v-model="shortAnswerText" 
                                class="bg-slate-950 border-slate-700 text-white border-round-xl p-3 text-base"
                                placeholder="Masukkan jawaban..." 
                                @blur="saveShortAnswer"
                            />
                        </div>

                        <!-- 3. URAIAN -->
                        <div v-else-if="currentQuestion.question_type === 'uraian' && !hasDynamicInputs" class="p-fluid">
                            <label class="block text-sm text-slate-400 font-semibold mb-2">Tulis Jawaban Uraian Anda:</label>
                            <Textarea 
                                v-model="essayText" 
                                rows="6" 
                                class="bg-slate-950 border-slate-700 text-white border-round-xl p-3 text-base"
                                placeholder="Tulis jawaban lengkap di sini..." 
                                @blur="saveEssayAnswer"
                            />
                        </div>

                        <!-- 4. LIST (Dropdown selection) -->
                        <div v-else-if="currentQuestion.question_type === 'list'" class="p-fluid">
                            <label class="block text-sm text-slate-400 font-semibold mb-2">Pilih Jawaban:</label>
                            <Select 
                                v-model="listAnswerValue" 
                                :options="formatSelectOptions(currentQuestion.options)" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Pilih Opsi..." 
                                class="bg-slate-950 border-slate-700 text-white border-round-xl p-2 text-base"
                                @change="saveListAnswer"
                            />
                        </div>

                        <!-- 5. CHECKLIST (Multi-select) -->
                        <div v-else-if="currentQuestion.question_type === 'checklist' && !hasDynamicInputs" class="flex flex-column gap-3">
                            <div 
                                v-for="(val, optKey, idx) in currentQuestion.options" 
                                :key="optKey"
                                class="option-card-btn flex align-items-center gap-3 p-3 cursor-pointer"
                                :class="isChecklistSelected(optKey) ? 'selected' : 'unselected'"
                                @click="toggleChecklistOption(optKey, $event)"
                            >
                                <span 
                                    class="option-badge-circle"
                                    :class="isChecklistSelected(optKey) ? 'selected' : 'unselected'"
                                >
                                    <i v-if="isChecklistSelected(optKey)" class="pi pi-check text-xs"></i>
                                    <span v-else>{{ String.fromCharCode(65 + idx) }}</span>
                                </span>
                                <span class="text-sm md:text-base line-height-3" v-html="val"></span>
                            </div>
                        </div>

                        <!-- 6. TABEL BENAR / SALAH -->
                        <div v-else-if="currentQuestion.question_type === 'benar_salah'" class="flex flex-column gap-3">
                            <div 
                                v-for="(stmt, stmtKey) in currentQuestion.options?.statements" 
                                :key="stmtKey"
                                class="bg-slate-950/40 p-3 border-round-xl border border-slate-700 flex flex-column md:flex-row md:justify-content-between md:align-items-center gap-3"
                            >
                                <span class="text-sm md:text-base font-semibold text-slate-200">{{ stmtKey }}. <span v-html="stmt"></span></span>
                                <div class="flex gap-2 align-self-end md:align-self-center">
                                    <Button 
                                        label="BENAR" 
                                        size="small" 
                                        :severity="getTrueFalseActive(stmtKey, 'B') ? 'success' : 'secondary'"
                                        :outlined="!getTrueFalseActive(stmtKey, 'B')"
                                        @click="saveTrueFalseAnswer(stmtKey, 'B')"
                                    />
                                    <Button 
                                        label="SALAH" 
                                        size="small" 
                                        :severity="getTrueFalseActive(stmtKey, 'S') ? 'danger' : 'secondary'"
                                        :outlined="!getTrueFalseActive(stmtKey, 'S')"
                                        @click="saveTrueFalseAnswer(stmtKey, 'S')"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- 7. PENJODOHAN (Modern left-to-right interactive clicks) -->
                        <div v-else-if="currentQuestion.question_type === 'penjodohan'" class="flex flex-column gap-3">
                            <div class="p-3 bg-slate-900/50 border border-slate-800 border-round-xl relative" ref="matchingContainerRef">
                                <!-- SVG Overlay untuk menggambar garis jodohnya -->
                                <svg class="absolute top-0 left-0 w-full h-full pointer-events-none" style="z-index: 10;">
                                    <line 
                                        v-for="line in matchingLines" 
                                        :key="line.id" 
                                        :x1="line.x1" 
                                        :y1="line.y1" 
                                        :x2="line.x2" 
                                        :y2="line.y2" 
                                        :stroke="line.color" 
                                        stroke-width="3" 
                                        stroke-linecap="round"
                                        stroke-dasharray="4,4"
                                        class="animate-dash-line"
                                    />
                                </svg>
                                
                                <div class="text-xs text-amber-400 font-semibold mb-3 flex align-items-center gap-1">
                                    <i class="pi pi-info-circle animate-pulse"></i>
                                    <span>Cara menjawab: Klik premis di kolom KIRI, lalu klik jodohnya di kolom KANAN.</span>
                                </div>
                                <div class="grid grid-nogutter gap-3 relative" style="z-index: 20;">
                                    <!-- Kolom Kiri: Premis -->
                                    <div class="col-12 md:col-6 flex flex-column gap-2">
                                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Premis (Kiri)</span>
                                        <div 
                                            v-for="(prem, pKey) in currentQuestion.options?.premises" 
                                            :key="pKey"
                                            class="p-3 border-round-xl border cursor-pointer transition-all duration-150 flex align-items-center justify-content-between gap-2 relative"
                                            :class="getPremiseClass(pKey)"
                                            @click="clickPremise(pKey, $event)"
                                        >
                                            <div class="flex align-items-center gap-3 w-9">
                                                <span class="font-bold text-sm text-blue-400 font-mono">{{ pKey }}</span>
                                                <span class="text-sm md:text-base text-slate-200" v-html="prem"></span>
                                            </div>
                                            
                                            <!-- Hubungan status -->
                                            <div class="flex align-items-center gap-2">
                                                <Tag 
                                                    v-if="matchingAnswers[pKey]" 
                                                    :value="'Terhubung: ' + matchingAnswers[pKey]" 
                                                    class="bg-slate-800 text-white font-bold text-xs"
                                                    :style="{ border: `1px solid ${getPremiseColor(pKey)}`, color: `${getPremiseColor(pKey)} !important` }"
                                                />
                                                <Button 
                                                    v-if="matchingAnswers[pKey]" 
                                                    icon="pi pi-times" 
                                                    severity="danger" 
                                                    text 
                                                    rounded 
                                                    size="small" 
                                                    class="h-2rem w-2rem"
                                                    @click.stop="clearMatchingPair(pKey)"
                                                />
                                            </div>
                                            <!-- Jangkar Dot Kanan -->
                                            <div 
                                                :id="'premise-dot-' + pKey" 
                                                class="w-2 h-2 border-circle absolute" 
                                                :style="{ 
                                                    right: '-4px', 
                                                    top: '50%', 
                                                    transform: 'translateY(-50%)', 
                                                    zIndex: 30, 
                                                    backgroundColor: matchingAnswers[pKey] ? getPremiseColor(pKey) : '#64748b',
                                                    boxShadow: matchingAnswers[pKey] ? `0 0 8px ${getPremiseColor(pKey)}` : 'none'
                                                }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Kolom Kanan: Target -->
                                    <div class="col-12 md:col-5 flex flex-column gap-2 ml-auto">
                                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Target Jodoh (Kanan)</span>
                                        <div 
                                            v-for="(targ, tKey) in currentQuestion.options?.targets" 
                                            :key="tKey"
                                            class="p-3 border-round-xl border cursor-pointer transition-all duration-150 flex align-items-center justify-content-between gap-2 relative"
                                            :class="getTargetClass(tKey)"
                                            @click="clickTarget(tKey, $event)"
                                        >
                                            <!-- Jangkar Dot Kiri -->
                                            <div 
                                                :id="'target-dot-' + tKey" 
                                                class="w-2 h-2 border-circle absolute" 
                                                :style="{ 
                                                    left: '-4px', 
                                                    top: '50%', 
                                                    transform: 'translateY(-50%)', 
                                                    zIndex: 30, 
                                                    backgroundColor: getTargetConnectedPremise(tKey) ? getPremiseColor(getTargetConnectedPremise(tKey)) : '#64748b',
                                                    boxShadow: getTargetConnectedPremise(tKey) ? `0 0 8px ${getPremiseColor(getTargetConnectedPremise(tKey))}` : 'none'
                                                }"
                                            ></div>

                                            <div class="flex align-items-center gap-3">
                                                <span class="font-bold text-sm text-amber-400 font-mono">{{ tKey }}</span>
                                                <span class="text-sm md:text-base text-slate-200" v-html="targ"></span>
                                            </div>

                                            <div v-if="getTargetConnectedPremise(tKey)" class="flex align-items-center gap-2">
                                                <Tag 
                                                    :value="'Jodoh: ' + getTargetConnectedPremise(tKey)" 
                                                    class="bg-blue-600/20 text-blue-400 border border-blue-500 font-bold text-xs"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 10. MENGURUTKAN (SORTING) -->
                        <div v-else-if="currentQuestion.question_type === 'sorting'" class="flex flex-column gap-2">
                            <span class="block text-xs text-slate-400 mb-2">Gunakan tombol panah untuk menyusun urutan yang benar dari atas ke bawah:</span>
                            <div 
                                v-for="(itemKey, itemIdx) in sortingItems" 
                                :key="itemKey"
                                class="bg-slate-950/60 p-3 border-round-xl border border-slate-700 flex align-items-center justify-content-between gap-3"
                            >
                                <div class="flex align-items-center gap-3">
                                    <span class="font-bold text-blue-400 font-mono">#{{ itemIdx + 1 }}</span>
                                    <span class="text-sm md:text-base text-slate-200">{{ currentQuestion.options?.items?.[itemKey] }}</span>
                                </div>
                                <div class="flex gap-1">
                                    <Button 
                                        icon="pi pi-arrow-up" 
                                        severity="secondary" 
                                        text 
                                        rounded 
                                        :disabled="itemIdx === 0" 
                                        @click="moveSortingItem(itemIdx, -1)" 
                                    />
                                    <Button 
                                        icon="pi pi-arrow-down" 
                                        severity="secondary" 
                                        text 
                                        rounded 
                                        :disabled="itemIdx === sortingItems.length - 1" 
                                        @click="moveSortingItem(itemIdx, 1)" 
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER ACTIONS -->
                    <div class="flex justify-content-between align-items-center gap-3 border-top-1 border-slate-700 pt-4 mt-auto">
                        <Button 
                            severity="secondary" 
                            :disabled="currentIdx === 0" 
                            @click="navigateQuestion(currentIdx - 1)" 
                            class="footer-btn"
                            v-tooltip.top="'Soal Sebelumnya'"
                        >
                            <i class="pi pi-chevron-left md:mr-1"></i>
                            <span class="mobile-hide">Sebelumnya</span>
                        </Button>
                        
                        <Button 
                            :severity="answers[currentQuestion.id]?.is_doubtful ? 'warning' : 'secondary'" 
                            :outlined="!answers[currentQuestion.id]?.is_doubtful"
                            @click="toggleDoubtful" 
                            class="footer-btn"
                            v-tooltip.top="'Tandai Ragu-Ragu'"
                        >
                            <i :class="[answers[currentQuestion.id]?.is_doubtful ? 'pi pi-bookmark-fill' : 'pi pi-bookmark', 'md:mr-1']"></i>
                            <span class="mobile-hide">{{ answers[currentQuestion.id]?.is_doubtful ? 'Ragu-Ragu Aktif' : 'Tandai Ragu-Ragu' }}</span>
                        </Button>

                        <Button 
                            v-if="currentIdx < questions.length - 1"
                            @click="navigateQuestion(currentIdx + 1)" 
                            class="footer-btn"
                            v-tooltip.top="'Soal Selanjutnya'"
                        >
                            <span class="mobile-hide">Selanjutnya</span>
                            <i class="pi pi-chevron-right md:ml-1"></i>
                        </Button>
                        <Button 
                            v-else
                            severity="success"
                            @click="openSubmitConfirmModal" 
                            class="footer-btn finish-btn"
                            v-tooltip.top="'Kumpulkan Ujian'"
                        >
                            <i class="pi pi-check-circle md:mr-1"></i>
                            <span class="mobile-hide">Selesai Ujian</span>
                            <span class="mobile-show">Selesai</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PETA SOAL POPUP DIALOG -->
        <Dialog 
            v-model:visible="displayMapModal" 
            header="Peta Soal / Navigasi Ujian" 
            :modal="true" 
            :style="{ width: '480px' }"
            class="bg-slate-900 border border-slate-800 text-slate-100"
        >
            <div class="flex flex-column gap-4 text-slate-300">
                <div class="text-sm text-slate-400">
                    Klik nomor soal di bawah untuk beralih pertanyaan:
                </div>
                <div class="grid grid-nogutter gap-2 justify-content-start">
                    <button 
                        v-for="(q, idx) in questions" 
                        :key="q.id"
                        class="nav-num-btn"
                        :class="getNumButtonClass(q.id, idx)"
                        @click="navigateQuestion(idx); displayMapModal = false;"
                    >
                        {{ idx + 1 }}
                    </button>
                </div>

                <div class="border-top-1 border-slate-700 pt-3 flex flex-column gap-2 text-xs text-slate-400">
                    <div class="flex align-items-center gap-2">
                        <span class="w-1rem h-1rem bg-blue-600 border-round"></span>
                        <span>Sudah Dijawab</span>
                    </div>
                    <div class="flex align-items-center gap-2">
                        <span class="w-1rem h-1rem bg-yellow-500 border-round"></span>
                        <span>Ragu-Ragu</span>
                    </div>
                    <div class="flex align-items-center gap-2">
                        <span class="w-1rem h-1rem bg-slate-950 border border-slate-700 border-round"></span>
                        <span>Belum Dijawab (Hitam)</span>
                    </div>
                </div>

                <Button 
                    label="KUMPULKAN UJIAN SEKARANG" 
                    severity="success" 
                    class="w-full mt-2 py-3 font-bold border-round-xl bg-emerald-600 hover:bg-emerald-500 text-white" 
                    icon="pi pi-send"
                    @click="displayMapModal = false; openSubmitConfirmModal();" 
                />
            </div>
        </Dialog>

        <!-- SUBMIT CONFIRM DIALOG -->
        <Dialog v-model:visible="displaySubmitModal" header="Konfirmasi Selesai Ujian" :modal="true" :style="{ width: '450px' }">
            <div class="flex flex-column gap-3 text-slate-300">
                <div class="text-center p-3 bg-slate-900 border-round-xl border border-slate-800">
                    <i class="pi pi-info-circle text-amber-500 text-4xl mb-2"></i>
                    <h3 class="text-white font-bold m-0 mb-2">Apakah Anda Yakin?</h3>
                    <p class="m-0 text-sm text-slate-400">Setelah dikumpulkan, Anda tidak dapat mengubah jawaban Anda lagi.</p>
                </div>

                <div class="flex flex-column gap-2 text-sm">
                    <div class="flex justify-content-between border-bottom-1 border-slate-700 pb-2">
                        <span>Total Soal:</span>
                        <b class="text-white">{{ questions.length }} Soal</b>
                    </div>
                    <div class="flex justify-content-between border-bottom-1 border-slate-700 pb-2">
                        <span>Sudah Dijawab:</span>
                        <b class="text-emerald-400">{{ answeredCount }} Soal</b>
                    </div>
                    <div class="flex justify-content-between border-bottom-1 border-slate-700 pb-2">
                        <span>Ragu-Ragu:</span>
                        <b class="text-amber-400">{{ doubtfulCount }} Soal</b>
                    </div>
                    <div class="flex justify-content-between">
                        <span>Belum Dijawab:</span>
                        <b class="text-red-400">{{ questions.length - answeredCount }} Soal</b>
                    </div>
                </div>

                <div v-if="exam.must_complete_all && isSubmitBlocked" class="p-3 bg-red-950/40 border-round border border-red-500 text-red-400 text-sm">
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    <strong>Ujian Belum Lengkap:</strong> Anda wajib menjawab <b>semua soal</b> dan tidak boleh memiliki soal dengan status <b>ragu-ragu</b> sebelum mengumpulkan ujian ini.
                </div>
                <div v-else-if="questions.length - answeredCount > 0" class="p-3 bg-red-950/20 border-round border border-red-500 text-red-400 text-xs">
                    <i class="pi pi-exclamation-triangle mr-1"></i>
                    Peringatan: Masih ada soal yang <b>belum dijawab</b>. Nilai Anda akan dihitung berdasarkan jawaban tersimpan.
                </div>

                <div class="flex justify-content-end gap-2 mt-3">
                    <Button label="Batal" severity="secondary" text @click="displaySubmitModal = false" />
                    <Button label="Kumpulkan" severity="success" icon="pi pi-send" :loading="isSubmitting" @click="submitExam" :disabled="isSubmitBlocked" />
                </div>
            </div>
        </Dialog>

        <!-- IMAGE ZOOM MODAL -->
        <Dialog 
            v-model:visible="displayZoomModal" 
            header="Detail Gambar Soal" 
            :modal="true" 
            :dismissableMask="true" 
            :style="{ width: '800px', maxWidth: '95vw' }"
            class="bg-slate-900 border border-slate-800 text-slate-100"
        >
            <div class="flex justify-content-center align-items-center p-2 bg-slate-950 border-round-xl border border-slate-800 overflow-hidden">
                <img 
                    :src="zoomImageUrl" 
                    class="max-w-full zoomed-image-el" 
                    style="object-fit: contain; max-height: 70vh; border-radius: 8px;" 
                    @click="displayZoomModal = false"
                />
            </div>
        </Dialog>

        <!-- DRY RUN RESULT MODAL -->
        <Dialog 
            v-model:visible="displayDryRunResultModal" 
            header="Hasil Simulasi Ujian CBT (Dry Run)" 
            :modal="true" 
            :closable="false"
            :style="{ width: '700px', maxWidth: '95vw' }"
            class="bg-slate-900 border border-slate-700 text-white"
        >
            <div class="p-2 flex flex-column gap-3">
                <div class="p-3 bg-amber-500/10 border-round-xl border border-amber-500 text-amber-300 text-sm flex align-items-center gap-2">
                    <i class="pi pi-info-circle text-lg"></i>
                    <span>Ini adalah <strong>Hasil Simulasi / Dry Run</strong>. Tidak ada data jawaban atau skor yang disimpan ke database portal.</span>
                </div>

                <div class="grid p-fluid">
                    <div class="col-12 md:col-6">
                        <div class="p-4 bg-slate-950 border border-slate-800 border-round-2xl text-center flex flex-column align-items-center justify-content-center h-full">
                            <span class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-1">Skor Simulasi Anda</span>
                            <h1 class="text-5xl font-mono font-bold text-emerald-400 m-0">{{ dryRunResults.percentage }}</h1>
                            <span class="text-xs text-slate-400 mt-1">Total Poin: {{ dryRunResults.totalPoints }} / {{ dryRunResults.maxPoints }}</span>
                        </div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="p-3 bg-slate-950 border border-slate-800 border-round-2xl flex flex-column gap-2 text-sm">
                            <div class="flex justify-content-between border-bottom-1 border-slate-800 pb-1">
                                <span class="text-slate-400">Total Soal:</span>
                                <strong class="text-white">{{ dryRunResults.totalQuestions }} Soal</strong>
                            </div>
                            <div class="flex justify-content-between border-bottom-1 border-slate-800 pb-1">
                                <span class="text-slate-400">Soal Terjawab:</span>
                                <strong class="text-blue-400">{{ dryRunResults.answeredCount }} Soal</strong>
                            </div>
                            <div class="flex justify-content-between border-bottom-1 border-slate-800 pb-1">
                                <span class="text-slate-400">Benar:</span>
                                <strong class="text-emerald-400">{{ dryRunResults.correctCount }} Soal</strong>
                            </div>
                            <div class="flex justify-content-between">
                                <span class="text-slate-400">Salah / Uraian / Kosong:</span>
                                <strong class="text-red-400">{{ dryRunResults.incorrectCount }} Soal</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rincian Soal -->
                <div class="max-h-15rem overflow-y-auto surface-950 border-round-xl border border-slate-800 p-2">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="text-slate-400 border-bottom-1 border-slate-800">
                                <th class="p-2">No</th>
                                <th class="p-2">Tipe Soal</th>
                                <th class="p-2">Status</th>
                                <th class="p-2 text-right">Poin Diperoleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in dryRunResults.details" :key="item.number" class="border-bottom-1 border-slate-800/60">
                                <td class="p-2 font-bold text-white">#{{ item.number }}</td>
                                <td class="p-2 text-slate-300">{{ item.question_type }}</td>
                                <td class="p-2">
                                    <Tag 
                                        :value="item.is_correct === true ? 'Benar' : (item.is_correct === false ? 'Salah' : 'Uraian (Perlu Periksa Manual)')"
                                        :severity="item.is_correct === true ? 'success' : (item.is_correct === false ? 'danger' : 'info')"
                                        class="text-xs"
                                    />
                                </td>
                                <td class="p-2 text-right font-mono font-bold" :class="item.points_earned > 0 ? 'text-emerald-400' : 'text-slate-400'">
                                    {{ item.points_earned }} / {{ item.max_score }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-content-end gap-2 mt-2 pt-2 border-top-1 border-slate-800">
                    <Button label="Tinjau Lembar Soal" severity="secondary" outlined size="small" @click="displayDryRunResultModal = false" />
                    <Button label="Kembali ke Daftar Ujian" icon="pi pi-arrow-left" severity="primary" size="small" class="font-bold" @click="exitDryRun" />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Chip from 'primevue/chip';
import axios from 'axios';

const props = defineProps({
    exam: Object,
    studentExam: Object,
    questions: Array,
    initialAnswers: Object,
    remainingSeconds: Number,
    isDryRun: {
        type: Boolean,
        default: false,
    },
});

const toast = useToast();

const currentIdx = ref(0);
const currentQuestion = computed(() => props.questions[currentIdx.value] || {});

// State jawaban lokal
const answers = ref(props.initialAnswers || {});

// State spesifik input per soal (untuk disinkronisasi ketika navigasi soal)
const shortAnswerText = ref('');
const essayText = ref('');
const listAnswerValue = ref(null);
const matchingAnswers = ref({});
const sortingItems = ref([]);

const displaySubmitModal = ref(false);
const displayDryRunResultModal = ref(false);
const dryRunResults = ref({
    totalQuestions: 0,
    answeredCount: 0,
    correctCount: 0,
    incorrectCount: 0,
    totalPoints: 0,
    maxPoints: 0,
    percentage: 0,
    details: [],
});

const exitDryRun = () => {
    router.visit('/cbt/exams');
};

const calculateDryRunScore = () => {
    let totalMaxScore = 0;
    let totalPointsEarned = 0;
    let correctCount = 0;
    let answered = 0;
    const details = [];

    props.questions.forEach((q, idx) => {
        const maxScore = parseFloat(q.score) || 10;
        totalMaxScore += maxScore;

        const ans = answers.value[q.id];
        const selected = ans ? ans.selected_answer : null;
        const correct = q.correct_answer;
        const type = q.question_type;

        let isCorrect = false;
        let pointsEarned = 0;

        const hasSelected = selected !== null && selected !== undefined && selected !== '' && 
            (!Array.isArray(selected) || selected.length > 0) &&
            (typeof selected !== 'object' || Object.keys(selected).length > 0);

        if (hasSelected) {
            answered++;
        }

        if (hasSelected) {
            if (type === 'pilihan_ganda' || type === 'survey' || type === 'skor_berbeda') {
                const selStr = Array.isArray(selected) ? (selected[0] ?? '') : selected;
                const corrStr = Array.isArray(correct) ? (correct[0] ?? '') : correct;
                if (String(selStr).trim().toUpperCase() === String(corrStr).trim().toUpperCase()) {
                    isCorrect = true;
                    pointsEarned = maxScore;
                }
            } else if (type === 'isian_singkat') {
                const selClean = String(Array.isArray(selected) ? selected.join(' ') : selected).trim().toLowerCase();
                const corrList = Array.isArray(correct) ? correct : [correct];
                const flatCorr = corrList.map(c => String(c).trim().toLowerCase());
                if (flatCorr.includes(selClean)) {
                    isCorrect = true;
                    pointsEarned = maxScore;
                }
            } else if (type === 'checklist' || type === 'list') {
                const selArr = Array.isArray(selected) ? selected.map(s => String(s).trim().toUpperCase()) : [String(selected).trim().toUpperCase()];
                const corrArr = Array.isArray(correct) ? correct.map(c => String(c).trim().toUpperCase()) : [String(correct).trim().toUpperCase()];
                if (selArr.length === corrArr.length && selArr.every(v => corrArr.includes(v))) {
                    isCorrect = true;
                    pointsEarned = maxScore;
                }
            } else if (type === 'benar_salah') {
                if (typeof correct === 'object' && correct !== null && typeof selected === 'object' && selected !== null) {
                    let allMatch = true;
                    const keys = Object.keys(correct);
                    for (const k of keys) {
                        if (String(selected[k]).toUpperCase() !== String(correct[k]).toUpperCase()) {
                            allMatch = false;
                            break;
                        }
                    }
                    if (allMatch && keys.length > 0) {
                        isCorrect = true;
                        pointsEarned = maxScore;
                    }
                }
            } else if (type === 'penjodohan') {
                if (typeof correct === 'object' && correct !== null && typeof selected === 'object' && selected !== null) {
                    let allMatch = true;
                    const keys = Object.keys(correct);
                    for (const k of keys) {
                        if (String(selected[k]) !== String(correct[k])) {
                            allMatch = false;
                            break;
                        }
                    }
                    if (allMatch && keys.length > 0) {
                        isCorrect = true;
                        pointsEarned = maxScore;
                    }
                }
            } else if (type === 'uraian') {
                isCorrect = null;
                pointsEarned = 0;
            }
        }

        if (isCorrect === true) {
            correctCount++;
        }
        totalPointsEarned += pointsEarned;

        details.push({
            number: idx + 1,
            question_type: type,
            is_correct: isCorrect,
            points_earned: Math.round(pointsEarned * 100) / 100,
            max_score: maxScore,
            selected_answer: selected,
            correct_answer: correct,
        });
    });

    const percentage = totalMaxScore > 0 ? Math.round((totalPointsEarned / totalMaxScore) * 100) : 0;

    dryRunResults.value = {
        totalQuestions: props.questions.length,
        answeredCount: answered,
        correctCount: correctCount,
        incorrectCount: props.questions.length - correctCount,
        totalPoints: Math.round(totalPointsEarned * 100) / 100,
        maxPoints: Math.round(totalMaxScore * 100) / 100,
        percentage: percentage,
        details: details,
    };

    displayDryRunResultModal.value = true;
};

const displayZoomModal = ref(false);
const zoomImageUrl = ref('');

// Proctoring / Anti-Cheat State
const warningCount = ref(props.studentExam.warning_count || 0);
// Backend sends ISO 8601 with +07:00 offset, so new Date() parses correctly.
const blockedUntil = ref(props.studentExam.blocked_until ? new Date(props.studentExam.blocked_until) : null);
const isBlocked = ref(props.studentExam.is_blocked || props.studentExam.status === 'blocked');
const isSuspended = ref(false);
const suspensionRemainingSeconds = ref(0);
const isFullscreenActive = ref(false);
const isAppleDevice = ref(false);
const reportingCheat = ref(false);
// Flag untuk mencegah double-fire saat sedang dalam proses redirect
const isRedirecting = ref(false);

const requestFullscreen = () => {
    const elem = document.documentElement;
    try {
        if (elem.requestFullscreen) {
            elem.requestFullscreen().catch(() => {});
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen().catch(() => {});
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen().catch(() => {});
        }
    } catch (e) {
        // Silently ignore gesture requirement exception when called programmatically
    }
};

const handleFullscreenChange = () => {
    isFullscreenActive.value = isAppleDevice.value || !!document.fullscreenElement;
    if (!isFullscreenActive.value && !isBlocked.value && !isSuspended.value && !isRedirecting.value) {
        reportCheatWarning();
    }
};

const handleVisibilityChange = () => {
    // Guard isFullscreenActive: jika overlay fullscreen sedang aktif (exam sudah terkunci
    // karena belum fullscreen), jangan tambah pelanggaran — siswa sudah tidak bisa
    // melihat soal apapun.
    // Catatan: pada iOS/Mac, isFullscreenActive selalu true (Apple tidak izinkan
    // fullscreen API), jadi guard ini tidak pernah memblokir event di perangkat Apple.
    if (document.visibilityState === 'hidden'
        && !isBlocked.value
        && !isSuspended.value
        && !isRedirecting.value
        && isFullscreenActive.value  // ← Jangan hitung jika exam sedang dalam mode overlay
    ) {
        reportCheatWarning();
    }
};

const handleWindowBlur = () => {
    // Guard isFullscreenActive: sama dengan handleVisibilityChange —
    // blur saat fullscreen overlay sedang tampil tidak boleh dihitung sebagai pelanggaran
    // karena siswa memang sudah terkunci dari soal.
    if (!isBlocked.value
        && !isSuspended.value
        && !isRedirecting.value
        && isFullscreenActive.value  // ← Jangan hitung jika exam sedang dalam mode overlay
    ) {
        reportCheatWarning();
    }
};

const reportCheatWarning = async () => {
    if (props.isDryRun) {
        warningCount.value++;
        const warnNum = warningCount.value;
        if (warnNum === 1 || warnNum === 2) {
            toast.add({
                severity: 'warn',
                summary: `[Simulasi] Pelanggaran Fokus ke-${warnNum}`,
                detail: `Siswa terdeteksi keluar dari layar ujian. Pada ujian riil, layar akan dikunci selama ${warnNum === 1 ? '1 menit' : '5 menit'}.`,
                life: 5000
            });
        } else if (warnNum === 3) {
            toast.add({
                severity: 'error',
                summary: `[Simulasi] Pelanggaran Fokus ke-3`,
                detail: `Pada ujian riil, siswa akan otomatis dikeluarkan (logout) dan memerlukan token pengawas untuk masuk kembali.`,
                life: 6000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: `[Simulasi] Pelanggaran Fokus ke-4 (Maksimal)`,
                detail: `Pada ujian riil, sesi ujian siswa akan otomatis diblokir permanen dan dikumpulkan paksa.`,
                life: 6000
            });
        }
        return;
    }

    if (isBlocked.value || isSuspended.value || reportingCheat.value || isRedirecting.value) return;
    reportingCheat.value = true;
    try {
        const response = await axios.post(route('student.cbt.cheat-warning', props.exam.id));
        const data = response.data;
        warningCount.value = data.warning_count;

        if (data.warning_count === 1 || data.warning_count === 2) {
            // Peringatan 1: lock 1 menit | Peringatan 2: lock 5 menit
            // Backend mengirim blocked_until sebagai ISO 8601 dengan timezone
            if (data.blocked_until) {
                blockedUntil.value = new Date(data.blocked_until);
                isSuspended.value = true;
                startSuspensionTimer();
            }
            const minutes = data.warning_count === 1 ? '1 menit' : '5 menit';
            toast.add({
                severity: 'warn',
                summary: `Peringatan ke-${data.warning_count}`,
                detail: `Layar ujian dikunci selama ${minutes} karena Anda keluar dari halaman ujian.`,
                life: 6000
            });
        } else if (data.warning_count === 3 || data.status === 'logged_out') {
            // Peringatan 3: logout paksa — harus pakai token lagi
            isRedirecting.value = true;
            toast.add({
                severity: 'error',
                summary: 'Peringatan ke-3: Dikeluarkan!',
                detail: 'Anda otomatis dikeluarkan dari ujian karena melanggar aturan fokus 3 kali. Minta token ulang kepada pengawas.',
                life: 6000
            });
            setTimeout(() => {
                router.visit(route('student.cbt.index'));
            }, 3500);
        } else if (data.warning_count >= 4 || data.status === 'submitted' || data.is_blocked) {
            // Peringatan 4+: ban permanen & submit otomatis
            isRedirecting.value = true;
            isBlocked.value = true;
            toast.add({
                severity: 'error',
                summary: 'Peringatan ke-4: Diblokir Permanen!',
                detail: 'Ujian Anda telah dikumpulkan secara paksa dan akses diblokir permanen karena melanggar aturan fokus 4 kali.',
                life: 6000
            });
            setTimeout(() => {
                router.visit(route('student.cbt.index'));
            }, 4000);
        }
    } catch (error) {
        console.error('Failed to report cheat warning', error);
    } finally {
        reportingCheat.value = false;
    }
};

let suspensionInterval = null;
const startSuspensionTimer = () => {
    if (suspensionInterval) clearInterval(suspensionInterval);
    const updateTimer = () => {
        const now = new Date();
        if (blockedUntil.value && blockedUntil.value > now) {
            suspensionRemainingSeconds.value = Math.max(0, Math.round((blockedUntil.value - now) / 1000));
            isSuspended.value = true;
        } else {
            isSuspended.value = false;
            suspensionRemainingSeconds.value = 0;
            clearInterval(suspensionInterval);
            // requestFullscreen() cannot be called here due to user gesture requirement.
            // The template will automatically show the !isFullscreenActive overlay instead.
        }
    };
    updateTimer();
    suspensionInterval = setInterval(updateTimer, 1000);
};

let heartbeatInterval = null;
const startHeartbeatTimer = () => {
    if (props.isDryRun) return; // No heartbeat needed for dry run
    if (heartbeatInterval) clearInterval(heartbeatInterval);
    heartbeatInterval = setInterval(async () => {
        // Jangan heartbeat jika sedang dalam proses redirect
        if (isRedirecting.value) return;
        try {
            const response = await axios.post(route('student.cbt.heartbeat', props.exam.id));
            const data = response.data;
            if (data.status === 'blocked') {
                // Status blocked: tampilkan overlay ban permanen, lalu redirect
                isBlocked.value = true;
                isRedirecting.value = true;
                clearInterval(heartbeatInterval);
                toast.add({ severity: 'error', summary: 'Blokir Permanen', detail: data.message || 'Akses Anda diblokir permanen karena pelanggaran fokus.', life: 5000 });
                setTimeout(() => { router.visit(route('student.cbt.index')); }, 4000);
            } else if (data.status === 'logged_out') {
                // Status logged_out: dikeluarkan oleh proktor atau peringatan ke-3
                isRedirecting.value = true;
                clearInterval(heartbeatInterval);
                toast.add({ severity: 'error', summary: 'Dikeluarkan dari Ujian', detail: data.message || 'Anda dikeluarkan dari ujian oleh Pengawas.', life: 5000 });
                setTimeout(() => { router.visit(route('student.cbt.index')); }, 3500);
            } else if (data.status === 'submitted') {
                // Status submitted paksa: ban permanen aktif
                isBlocked.value = true;
                isRedirecting.value = true;
                clearInterval(heartbeatInterval);
                toast.add({ severity: 'error', summary: 'Ujian Selesai Paksa', detail: data.message || 'Ujian Anda telah dikumpulkan secara paksa karena pelanggaran fokus.', life: 5000 });
                setTimeout(() => { router.visit(route('student.cbt.index')); }, 4000);
            } else if (data.status === 'ended') {
                // Ruangan ditutup pengawas
                isRedirecting.value = true;
                clearInterval(heartbeatInterval);
                toast.add({ severity: 'info', summary: 'Ujian Selesai', detail: data.message || 'Sesi ujian di ruangan Anda telah ditutup.', life: 5000 });
                setTimeout(() => { router.visit(route('student.cbt.index')); }, 3000);
            } else if (data.status === 'suspensi') {
                // Suspensi sementara (peringatan 1 atau 2 dari server)
                warningCount.value = data.warning_count;
                if (data.remaining_seconds > 0) {
                    // Gunakan Date.now() + remaining_seconds * 1000 (timezone-safe)
                    blockedUntil.value = new Date(Date.now() + data.remaining_seconds * 1000);
                    isSuspended.value = true;
                    startSuspensionTimer();
                }
            }
        } catch (error) {
            console.error('Heartbeat error', error);
        }
    }, 10000);
};

const handleImageClick = (event) => {
    const target = event.target;
    if (target.tagName === 'IMG') {
        zoomImageUrl.value = target.src;
        displayZoomModal.value = true;
    }
};
const displayMapModal = ref(false);
const isSubmitting = ref(false);

// State interaksi matching (penjodohan)
const activePremiseKey = ref(null);
const matchingContainerRef = ref(null);
const matchingLines = ref([]);

const MATCHING_COLORS = [
    '#3b82f6', // Biru
    '#10b981', // Hijau
    '#f59e0b', // Amber/Kuning
    '#ec4899', // Pink
    '#8b5cf6', // Ungu
    '#06b6d4', // Cyan
    '#ef4444', // Merah
    '#14b8a6', // Teal
    '#f43f5e'  // Rose
];

const getPremiseColor = (pKey) => {
    const premisesKeys = Object.keys(currentQuestion.value.options?.premises || {});
    const pIndex = premisesKeys.indexOf(pKey);
    const colorIdx = pIndex !== -1 ? pIndex % MATCHING_COLORS.length : 0;
    return MATCHING_COLORS[colorIdx];
};

const updateMatchingLines = () => {
    if (!matchingContainerRef.value || currentQuestion.value.question_type !== 'penjodohan') {
        matchingLines.value = [];
        return;
    }
    nextTick(() => {
        const container = matchingContainerRef.value;
        if (!container) return;
        
        const containerRect = container.getBoundingClientRect();
        const newLines = [];
        
        Object.entries(matchingAnswers.value).forEach(([pKey, tKey]) => {
            if (!tKey) return;
            
            const pDot = document.getElementById(`premise-dot-${pKey}`);
            const tDot = document.getElementById(`target-dot-${tKey}`);
            
            if (pDot && tDot) {
                const pRect = pDot.getBoundingClientRect();
                const tRect = tDot.getBoundingClientRect();
                const strokeColor = getPremiseColor(pKey);
                
                newLines.push({
                    id: `${pKey}-${tKey}`,
                    x1: pRect.left + pRect.width / 2 - containerRect.left,
                    y1: pRect.top + pRect.height / 2 - containerRect.top,
                    x2: tRect.left + tRect.width / 2 - containerRect.left,
                    y2: tRect.top + tRect.height / 2 - containerRect.top,
                    color: strokeColor
                });
            }
        });
        
        matchingLines.value = newLines;
    });
};

const handleResize = () => {
    updateMatchingLines();
};

watch(matchingAnswers, () => {
    updateMatchingLines();
}, { deep: true });

// TIMER LOGIC (Format Menit:Detik kumulatif, 2-digit)
const timeLeft = ref(props.remainingSeconds || 0);
let timerInterval = null;

const formattedRemainingTime = computed(() => {
    if (timeLeft.value <= 0) return '00:00';
    const mins = Math.floor(timeLeft.value / 60);
    const secs = Math.floor(timeLeft.value % 60);
    return [
        mins.toString().padStart(2, '0'),
        secs.toString().padStart(2, '0')
    ].join(':');
});

onMounted(() => {
    syncLocalInputs();
    
    // Start countdown
    timerInterval = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            clearInterval(timerInterval);
            autoSubmitExam();
        }
    }, 1000);

    window.addEventListener('resize', handleResize);

    // Apple device check for fullscreen exception
    const ua = navigator.userAgent || navigator.vendor || window.opera;
    isAppleDevice.value = (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) || /Macintosh|Mac OS X/.test(ua) || (navigator.platform && /Mac|iPhone|iPad|iPod/.test(navigator.platform));

    // Initial fullscreen state check
    isFullscreenActive.value = isAppleDevice.value || !!document.fullscreenElement;
    
    // Check initial blocked/suspended status
    if (isBlocked.value) {
        // Already blocked permanently
    } else if (blockedUntil.value && blockedUntil.value > new Date()) {
        isSuspended.value = true;
        startSuspensionTimer();
    }
    
    // Attempt fullscreen
    requestFullscreen();

    // Bind event listeners
    window.addEventListener('blur', handleWindowBlur);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    
    // Start status heartbeat
    startHeartbeatTimer();
});

onUnmounted(() => {
    clearInterval(timerInterval);
    if (suspensionInterval) clearInterval(suspensionInterval);
    if (heartbeatInterval) clearInterval(heartbeatInterval);
    window.removeEventListener('resize', handleResize);
    window.removeEventListener('blur', handleWindowBlur);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
});

// SINKRONISASI INPUT SETIAP NAVIGASI SOAL
const syncLocalInputs = () => {
    const q = currentQuestion.value;
    const ans = answers.value[q.id]?.selected_answer;

    shortAnswerText.value = '';
    essayText.value = '';
    listAnswerValue.value = null;
    matchingAnswers.value = {};
    sortingItems.value = [];
    activePremiseKey.value = null; // Reset matching state

    if (q.question_type === 'isian_singkat') {
        shortAnswerText.value = ans ? (Array.isArray(ans) ? ans.join(', ') : (typeof ans === 'object' ? '' : ans)) : '';
    } else if (q.question_type === 'uraian') {
        essayText.value = ans ? (Array.isArray(ans) ? ans.join('\n') : (typeof ans === 'object' ? '' : ans)) : '';
    } else if (q.question_type === 'list') {
        listAnswerValue.value = ans ? (Array.isArray(ans) ? ans[0] : ans) : null;
    } else if (q.question_type === 'penjodohan') {
        matchingAnswers.value = ans || {};
    } else if (q.question_type === 'sorting') {
        // Jika sudah ada urutan disimpan siswa, pakai itu. Jika tidak, pakai item default.
        if (Array.isArray(ans) && ans.length > 0) {
            sortingItems.value = [...ans];
        } else {
            sortingItems.value = Object.keys(q.options?.items || {});
        }
    }

    // Restore dynamic inline inputs if present
    nextTick(() => {
        restoreDynamicInputs();
        updateMatchingLines();
        setTimeout(updateMatchingLines, 200);
        setTimeout(updateMatchingLines, 500);
    });
};

const hasDynamicInputs = computed(() => {
    return currentQuestion.value.question_text?.includes('cbt-dynamic-');
});

const restoreDynamicInputs = () => {
    const qId = currentQuestion.value.id;
    const ans = answers.value[qId]?.selected_answer;
    
    // Restore text inputs / textareas
    const inputs = document.querySelectorAll('.cbt-dynamic-input');
    inputs.forEach(input => {
        const subId = input.getAttribute('data-id');
        input.value = (ans && ans[subId]) ? ans[subId] : '';
    });
    
    // Restore radios
    const radios = document.querySelectorAll('.cbt-dynamic-radio');
    radios.forEach(radio => {
        const subId = radio.getAttribute('data-id');
        const val = radio.value;
        radio.checked = (ans && ans[subId] === val);
    });

    // Restore checkboxes
    const checkboxes = document.querySelectorAll('.cbt-dynamic-checkbox');
    checkboxes.forEach(cb => {
        const val = cb.value;
        cb.checked = (ans && Array.isArray(ans) && ans.includes(val));
    });
};

const handleDynamicChange = (event) => {
    const target = event.target;
    if (target.classList.contains('cbt-dynamic-radio')) {
        const qId = currentQuestion.value.id;
        const subId = target.getAttribute('data-id');
        const val = radioVal(target);
        
        let ans = answers.value[qId]?.selected_answer || {};
        if (typeof ans !== 'object' || Array.isArray(ans)) ans = {};
        
        ans[subId] = val;
        
        const isDoubtful = answers.value[qId]?.is_doubtful || false;
        saveAnswerToServer(qId, ans, isDoubtful);
    } else if (target.classList.contains('cbt-dynamic-checkbox')) {
        const qId = currentQuestion.value.id;
        
        // Kumpulkan semua checkbox dinamis yang tercentang di DOM
        const checkedVals = [];
        const checkedCBs = document.querySelectorAll('.cbt-dynamic-checkbox:checked');
        checkedCBs.forEach(cb => {
            checkedVals.push(cb.value);
        });
        
        const isDoubtful = answers.value[qId]?.is_doubtful || false;
        saveAnswerToServer(qId, checkedVals, isDoubtful);
    } else if (target.classList.contains('cbt-dynamic-input')) {
        const qId = currentQuestion.value.id;
        const subId = target.getAttribute('data-id');
        const val = target.value;
        
        let ans = answers.value[qId]?.selected_answer || {};
        if (typeof ans !== 'object' || Array.isArray(ans)) ans = {};
        
        ans[subId] = val;
        
        const isDoubtful = answers.value[qId]?.is_doubtful || false;
        saveAnswerToServer(qId, ans, isDoubtful);
    }
};

// Helper to get radio value safely
const radioVal = (el) => {
    return el.value;
};

const navigateQuestion = (idx) => {
    if (idx >= 0 && idx < props.questions.length) {
        currentIdx.value = idx;
        syncLocalInputs();
    }
};

// ==========================================
// PENANGANAN INPUT & AUTOSAVE JAWABAN
// ==========================================

const saveAnswerToServer = async (qId, selectedVal, isDoubtful) => {
    // Selalu update state lokal terlebih dahulu
    answers.value[qId] = {
        selected_answer: selectedVal,
        is_doubtful: isDoubtful
    };

    if (props.isDryRun) return; // Mode simulasi tidak mengirim ke database!

    if (isBlocked.value || isSuspended.value || isRedirecting.value) return;
    try {
        await axios.post(route('student.cbt.save-answer', props.exam.id), {
            cbt_question_id: qId,
            selected_answer: selectedVal,
            is_doubtful: isDoubtful
        });
    } catch (error) {
        if (error.response && (error.response.status === 404 || error.response.status === 400)) return;
        toast.add({ severity: 'error', summary: 'Error Koneksi', detail: 'Jawaban gagal terkirim ke server. Periksa koneksi internet Anda.', life: 4000 });
    }
};

// 1. Pilihan Ganda / 8. Survey / 9. Skor Berbeda
const selectSingleOption = (optKey, event) => {
    if (event && event.target.tagName === 'IMG') return;
    const qId = currentQuestion.value.id;
    const isDoubtful = answers.value[qId]?.is_doubtful || false;
    saveAnswerToServer(qId, optKey, isDoubtful);
};

// 2. Isian Singkat
const saveShortAnswer = () => {
    const qId = currentQuestion.value.id;
    const isDoubtful = answers.value[qId]?.is_doubtful || false;
    saveAnswerToServer(qId, shortAnswerText.value, isDoubtful);
};

// 3. Uraian
const saveEssayAnswer = () => {
    const qId = currentQuestion.value.id;
    const isDoubtful = answers.value[qId]?.is_doubtful || false;
    saveAnswerToServer(qId, essayText.value, isDoubtful);
};

// 4. Dropdown List
const formatSelectOptions = (options) => {
    if (!options) return [];
    return Object.entries(options).map(([k, v]) => ({ label: `${k}. ${v}`, value: k }));
};

const saveListAnswer = () => {
    const qId = currentQuestion.value.id;
    const isDoubtful = answers.value[qId]?.is_doubtful || false;
    saveAnswerToServer(qId, [listAnswerValue.value], isDoubtful);
};

// 5. Checklist (Kotak Centang)
const isChecklistSelected = (optKey) => {
    const qId = currentQuestion.value.id;
    const ans = answers.value[qId]?.selected_answer;
    return Array.isArray(ans) && ans.includes(optKey);
};

const toggleChecklistOption = (optKey, event) => {
    if (event && event.target.tagName === 'IMG') return;
    const qId = currentQuestion.value.id;
    let ans = answers.value[qId]?.selected_answer || [];
    if (!Array.isArray(ans)) ans = [];

    if (ans.includes(optKey)) {
        ans = ans.filter(item => item !== optKey);
    } else {
        ans.push(optKey);
    }

    const isDoubtful = answers.value[qId]?.is_doubtful || false;
    saveAnswerToServer(qId, ans, isDoubtful);
};

// 6. Benar / Salah
const getTrueFalseActive = (stmtKey, val) => {
    const qId = currentQuestion.value.id;
    const ans = answers.value[qId]?.selected_answer;
    return ans && ans[stmtKey] === val;
};

const saveTrueFalseAnswer = (stmtKey, val) => {
    const qId = currentQuestion.value.id;
    let ans = answers.value[qId]?.selected_answer || {};
    if (typeof ans !== 'object' || Array.isArray(ans)) ans = {};

    ans[stmtKey] = val;

    const isDoubtful = answers.value[qId]?.is_doubtful || false;
    saveAnswerToServer(qId, ans, isDoubtful);
};

// 7. Penjodohan (Interactive Clicks Left-to-Right)
const clickPremise = (pKey, event) => {
    if (event && event.target.tagName === 'IMG') return;
    if (activePremiseKey.value === pKey) {
        activePremiseKey.value = null;
    } else {
        activePremiseKey.value = pKey;
    }
};

const clickTarget = (tKey, event) => {
    if (event && event.target.tagName === 'IMG') return;
    if (activePremiseKey.value) {
        const pKey = activePremiseKey.value;
        saveMatchingAnswer(pKey, tKey);
        activePremiseKey.value = null; // reset selection
    } else {
        toast.add({ severity: 'info', summary: 'Info', detail: 'Pilih premis di sebelah kiri terlebih dahulu.', life: 2000 });
    }
};

const clearMatchingPair = (pKey) => {
    saveMatchingAnswer(pKey, null);
    if (activePremiseKey.value === pKey) {
        activePremiseKey.value = null;
    }
};

const getPremiseClass = (pKey) => {
    const isSelected = activePremiseKey.value === pKey;
    const isPaired = !!matchingAnswers.value[pKey];
    
    if (isSelected) {
        return 'bg-blue-600/30 border-blue-500 text-blue-300 ring-2 ring-blue-500 shadow-md scale-102';
    }
    if (isPaired) {
        return 'bg-slate-800/80 border-emerald-500/40 hover:border-emerald-500 text-slate-300';
    }
    return 'bg-slate-800/50 border-slate-700 hover:bg-slate-700 text-slate-300';
};

const getTargetClass = (tKey) => {
    const isPaired = Object.values(matchingAnswers.value).includes(tKey);
    const hasActivePremise = !!activePremiseKey.value;
    
    if (isPaired) {
        return 'bg-slate-800/80 border-blue-500/40 hover:border-blue-500 text-slate-300';
    }
    if (hasActivePremise) {
        return 'bg-slate-800/50 border-amber-500/30 hover:border-amber-400 text-slate-300 animate-pulse';
    }
    return 'bg-slate-800/50 border-slate-700 hover:bg-slate-700 text-slate-300';
};

const getTargetConnectedPremise = (tKey) => {
    const entry = Object.entries(matchingAnswers.value).find(([pKey, targetVal]) => targetVal === tKey);
    return entry ? entry[0] : null;
};

const formatMatchingTargets = (targets) => {
    if (!targets) return [];
    return Object.entries(targets).map(([k, v]) => ({ label: `${k}. ${v}`, value: k }));
};

const formatValueLabel = (targets, value) => {
    if (!targets || !value) return '';
    return `${value}. ${targets[value] || ''}`;
};

const saveMatchingAnswer = (pKey, tVal) => {
    const qId = currentQuestion.value.id;
    
    // Update local matchingAnswers state
    if (tVal === null) {
        delete matchingAnswers.value[pKey];
    } else {
        matchingAnswers.value[pKey] = tVal;
    }

    // Buat salinan bersih untuk disimpan ke server
    const ans = { ...matchingAnswers.value };

    const isDoubtful = answers.value[qId]?.is_doubtful || false;
    saveAnswerToServer(qId, ans, isDoubtful);
    
    updateMatchingLines();
};

// 10. Mengurutkan (Sorting)
const moveSortingItem = (index, direction) => {
    const targetIndex = index + direction;
    if (targetIndex >= 0 && targetIndex < sortingItems.value.length) {
        const temp = sortingItems.value[index];
        sortingItems.value[index] = sortingItems.value[targetIndex];
        sortingItems.value[targetIndex] = temp;

        const qId = currentQuestion.value.id;
        const isDoubtful = answers.value[qId]?.is_doubtful || false;
        saveAnswerToServer(qId, sortingItems.value, isDoubtful);
    }
};

// RAGU-RAGU TOGGLE
const toggleDoubtful = () => {
    const qId = currentQuestion.value.id;
    const ans = answers.value[qId];
    const selectedVal = ans ? ans.selected_answer : null;
    const currentDoubt = ans ? ans.is_doubtful : false;

    saveAnswerToServer(qId, selectedVal, !currentDoubt);
};

// COUNTERS FOR STATS
const answeredCount = computed(() => {
    return Object.entries(answers.value).filter(([qId, ans]) => {
        return ans.selected_answer !== null && ans.selected_answer !== '' && 
            (!Array.isArray(ans.selected_answer) || ans.selected_answer.length > 0) &&
            (typeof ans.selected_answer !== 'object' || Object.keys(ans.selected_answer).length > 0);
    }).length;
});

const doubtfulCount = computed(() => {
    return Object.values(answers.value).filter(ans => ans.is_doubtful).length;
});

const isSubmitBlocked = computed(() => {
    return props.exam.must_complete_all && (props.questions.length - answeredCount.value > 0 || doubtfulCount.value > 0);
});

const getNumButtonClass = (qId, idx) => {
    let classes = '';
    const ans = answers.value[qId];
    
    // Status isi
    const isAnswered = ans && ans.selected_answer !== null && ans.selected_answer !== '' && 
        (!Array.isArray(ans.selected_answer) || ans.selected_answer.length > 0) &&
        (typeof ans.selected_answer !== 'object' || Object.keys(ans.selected_answer).length > 0);

    const isDoubtful = ans && ans.is_doubtful;

    if (currentIdx.value === idx) {
        classes += ' current ';
    }

    if (isDoubtful) {
        classes += ' doubtful ';
    } else if (isAnswered) {
        classes += ' answered ';
    }

    return classes;
};

// ==========================================
// MUAT ULANG SOAL (IN-APP PARTIAL RELOAD)
// ==========================================
const isReloadingQuestions = ref(false);

const reloadQuestions = () => {
    if (isReloadingQuestions.value) return;

    // Amankan input teks yang sedang aktif sebelum reload
    const qType = currentQuestion.value?.question_type;
    if (qType === 'isian_singkat') {
        saveShortAnswer();
    } else if (qType === 'uraian') {
        saveEssayAnswer();
    }

    isReloadingQuestions.value = true;
    router.reload({
        only: ['questions'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            syncLocalInputs();
            toast.add({
                severity: 'info',
                summary: 'Soal Diperbarui',
                detail: 'Teks soal dan pilihan berhasil dimuat ulang dengan versi terbaru.',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Gagal Memuat Ulang',
                detail: 'Terjadi kendala jaringan saat memuat ulang soal. Silakan coba lagi.',
                life: 4000,
            });
        },
        onFinish: () => {
            isReloadingQuestions.value = false;
        },
    });
};

// SUBMIT EXAM LOGIC
const openSubmitConfirmModal = () => {
    displaySubmitModal.value = true;
};

const submitExam = () => {
    if (props.isDryRun) {
        displaySubmitModal.value = false;
        calculateDryRunScore();
        return;
    }

    isSubmitting.value = true;
    // Set isRedirecting agar event blur/visibility tidak menghitung pelanggaran
    // saat router melakukan navigasi setelah submit
    isRedirecting.value = true;
    router.post(route('student.cbt.submit', props.exam.id), {}, {
        onFinish: () => {
            isSubmitting.value = false;
            displaySubmitModal.value = false;
            // Reset isRedirecting jika submit gagal (misal: validasi server)
            isRedirecting.value = false;
        },
        onSuccess: () => {
            // Hentikan semua interval saat submit berhasil
            clearInterval(heartbeatInterval);
            clearInterval(timerInterval);
        }
    });
};

const autoSubmitExam = () => {
    if (props.isDryRun) {
        toast.add({ severity: 'info', summary: 'Waktu Habis (Simulasi)', detail: 'Waktu ujian simulasi telah habis.', life: 4000 });
        calculateDryRunScore();
        return;
    }

    // Set flag redirect & hentikan semua interval SEBELUM submit
    // agar heartbeat tidak terus berjalan dan menyebabkan double-action
    // (misal: heartbeat mendeteksi status 'submitted' lalu trigger overlay/redirect ulang)
    isRedirecting.value = true;
    clearInterval(heartbeatInterval);
    clearInterval(timerInterval);
    toast.add({ severity: 'info', summary: 'Waktu Habis', detail: 'Waktu ujian telah habis. Jawaban Anda otomatis tersimpan.', life: 4000 });
    router.post(route('student.cbt.submit', props.exam.id));
};
</script>

<style scoped>
.nav-num-btn {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 800;
    cursor: pointer;
    border: 1px solid #334155;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
    color: #64748b;
}

.nav-num-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.5);
    border-color: #475569;
    color: #f8fafc;
}

.nav-num-btn.answered {
    background: linear-gradient(145deg, #2563eb 0%, #1d4ed8 100%) !important;
    border-color: #60a5fa !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
}

.nav-num-btn.doubtful {
    background: linear-gradient(145deg, #f59e0b 0%, #d97706 100%) !important;
    border-color: #fcd34d !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
}

.nav-num-btn.current {
    outline: 3px solid #10b981 !important;
    border-color: #34d399 !important;
    transform: scale(1.08);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.5) !important;
    z-index: 10;
}

/* Option Cards (Pilihan Ganda & Checklist) */
.option-card-btn {
    border-radius: 14px;
    border-width: 1px;
    border-style: solid;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.option-card-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.35);
}

.option-card-btn.selected {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.35) 0%, rgba(30, 58, 138, 0.5) 100%) !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
}

.option-card-btn.unselected {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.7) 100%) !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}

.option-card-btn.unselected:hover {
    background: linear-gradient(135deg, rgba(51, 65, 85, 0.7) 0%, rgba(30, 41, 59, 0.8) 100%) !important;
    border-color: #475569 !important;
    color: #f8fafc !important;
}

.option-badge-circle {
    width: 34px;
    height: 34px;
    min-width: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
}

.option-badge-circle.selected {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #ffffff;
    border: 1px solid #93c5fd;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.5);
}

.option-badge-circle.unselected {
    background: #1e293b;
    color: #94a3b8;
    border: 1px solid #475569;
}

/* Dynamic input styles to guarantee high contrast font colors on dark theme */
:deep(.cbt-dynamic-input) {
    background-color: #020617 !important;
    color: #ffffff !important;
    border: 1px solid #475569 !important;
    border-radius: 6px !important;
    padding: 8px 12px !important;
    font-size: 1rem !important;
    outline: none !important;
}
:deep(.cbt-dynamic-input:focus) {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
}
:deep(input.cbt-dynamic-input) {
    display: inline-block !important;
    max-width: 250px !important;
    width: auto !important;
    text-align: center !important;
}
:deep(textarea.cbt-dynamic-input) {
    width: 100% !important;
    display: block !important;
}
:deep(.cbt-dynamic-radio), :deep(.cbt-dynamic-checkbox) {
    accent-color: #3b82f6 !important;
}

/* Image scaling styles for options and matching */
::v-deep(.cbt-matching-img),
:deep(.cbt-matching-img),
::v-deep(td img),
:deep(td img),
::v-deep(.text-sm img),
:deep(.text-sm img) {
    max-width: 180px !important;
    height: auto !important;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    border: 1px solid #334155;
    background-color: #0f172a;
    padding: 4px;
}

.animate-dash-line {
    animation: dash 1s linear infinite;
}

@keyframes dash {
    to {
        stroke-dashoffset: -20;
    }
}
:deep(img:not(.zoomed-image-el)) {
    cursor: zoom-in !important;
    transition: transform 0.2s ease-in-out;
}
:deep(img:not(.zoomed-image-el):hover) {
    transform: scale(1.02);
}
.zoomed-image-el {
    cursor: zoom-out !important;
}

.mobile-hide {
    display: inline;
}
.mobile-show {
    display: none;
}

.footer-btn {
    border-radius: 12px !important;
    font-weight: 700 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
}
.footer-btn:hover:not(:disabled) {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4) !important;
}

@media (max-width: 767px) {
    .mobile-hide {
        display: none !important;
    }
    .mobile-show {
        display: inline !important;
    }
    .footer-btn:not(.finish-btn) {
        width: 46px !important;
        height: 46px !important;
        padding: 0 !important;
    }
    .finish-btn {
        height: 46px !important;
        padding: 0 16px !important;
    }
}
</style>
