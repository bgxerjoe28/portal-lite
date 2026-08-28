<template>
<AppLayout>
    <!-- ===== CARD HEADER ===== -->
    <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
      <div class="flex justify-content-between align-items-center">
        <div>
          <h2 class="text-2xl font-bold m-0">Dashboard Kurikulum</h2>
          <span class="text-600 text-sm">
            Rekap beban mengajar guru – Tahun Ajaran {{ academicYear.name }}
          </span>
        </div>
      </div>
    </div>

    <!-- ===== CARD TABLE ===== -->
    <div class="surface-card p-4 border-round-lg shadow-1">
      <DataTable
        :value="rows"
        stripedRows
        showGridlines
        class="p-datatable-sm"
      >
        <Column field="name" header="Guru" />

        <Column field="target_jp" header="Target JP" class="text-center" />

        <Column field="realized_jp" header="JP Realisasi" class="text-center">
          <template #body="{ data }">
            <b>{{ data.realized_jp }}</b>
          </template>
        </Column>

        <Column header="Selisih" class="text-center">
          <template #body="{ data }">
            <span :class="diffColor(data.difference)">
              {{ signed(data.difference) }}
            </span>
          </template>
        </Column>

        <Column header="Status" class="text-center">
          <template #body="{ data }">
            <Tag
              :value="statusLabel(data.status)"
              :severity="statusSeverity(data.status)"
            />
          </template>
        </Column>

        <Column header="Aksi" class="text-center" style="width:120px">
          <template #body="{ data }">
            <Button
              icon="pi pi-search"
              text
              severity="primary"
              v-tooltip.top="'Detail Beban Guru'"
              @click="goDetail(data.teacher_id)"
            />
          </template>
        </Column>
      </DataTable>
    </div>
</AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  rows: Array,
  academicYear: Object,
})

const goDetail = (teacherId) => {
  router.get(route('teachers.workload', teacherId))
}

const signed = (v) => (v > 0 ? `+${v}` : v)

const diffColor = (v) => {
  if (v === 0) return 'text-green-600'
  if (v < 0) return 'text-red-600'
  return 'text-orange-500'
}

const statusLabel = (s) => {
  if (s === 'balanced') return 'Ideal'
  if (s === 'underload') return 'Kurang'
  return 'Lebih'
}

const statusSeverity = (s) => {
  if (s === 'balanced') return 'success'
  if (s === 'underload') return 'danger'
  return 'warning'
}
</script>
