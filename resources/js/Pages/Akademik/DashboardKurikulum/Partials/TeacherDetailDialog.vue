<template>
    <div class="surface-card p-4 border-round shadow-1">
    <h3 class="text-lg font-bold mb-3">Rekap Beban Mengajar Guru</h3>

    <DataTable
        :value="rowsSafe"
        stripedRows
        showGridlines
        size="small"
    >
        <Column field="name" header="Guru" />

        <Column header="Target (JP/Minggu)" class="text-center">
            <template #body="{ data }">
                {{ data.target_weekly }}
            </template>
        </Column>

        <Column header="Realisasi (JP/Minggu)" class="text-center">
            <template #body="{ data }">
                {{ data.realized_weekly }}
            </template>
        </Column>

        <Column header="Selisih" class="text-center">
            <template #body="{ data }">
                <span
                    class="font-bold"
                    :class="diffColor(data.diff_weekly)"
                >
                    {{ data.diff_weekly }}
                </span>
            </template>
        </Column>

        <Column header="Status" class="text-center">
            <template #body="{ data }">
                <Tag
                    :value="labelStatus(data.status)"
                    :severity="severityStatus(data.status)"
                />
            </template>
        </Column>

        <Column header="Semester" class="text-center">
            <template #body="{ data }">
                <small class="text-600">
                    {{ data.realized_semester }} / {{ data.target_semester }} JP
                </small>
            </template>
        </Column>

        <Column header="Detail" class="text-center">
            <template #body="{ data }">
                <Button
                    icon="pi pi-search"
                    size="small"
                    text
                    @click="openDetail(data)"
                />
            </template>
        </Column>
    </DataTable>
</div>
</template>

<script setup>
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

defineProps({
    teacher: Object
})
</script>
