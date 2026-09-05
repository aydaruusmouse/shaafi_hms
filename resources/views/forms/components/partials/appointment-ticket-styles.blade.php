<style>
    .appointment-ticket-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
    }

    .appointment-ticket-chip {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 2rem !important;
        height: 2rem !important;
        padding: 0 0.375rem !important;
        border-radius: 0.375rem !important;
        border: 2px solid transparent !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        box-shadow: none !important;
    }

    button.appointment-ticket-chip {
        appearance: none !important;
        background-image: none !important;
    }

    .appointment-ticket-chip--available {
        background-color: #22c55e !important;
        border-color: #16a34a !important;
        color: #ffffff !important;
    }

    .appointment-ticket-chip--taken {
        background-color: #ef4444 !important;
        border-color: #dc2626 !important;
        color: #ffffff !important;
    }

    .appointment-ticket-chip--cancelled {
        background-color: #9ca3af !important;
        border-color: #6b7280 !important;
        color: #ffffff !important;
    }

    .appointment-ticket-chip--default {
        background-color: #e5e7eb !important;
        border-color: #d1d5db !important;
        color: #374151 !important;
    }

    .appointment-ticket-chip--selected {
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px #2563eb !important;
        transform: scale(1.08);
    }

    .appointment-ticket-chip--disabled {
        opacity: 0.65 !important;
        cursor: not-allowed !important;
    }

    button.appointment-ticket-chip:not(.appointment-ticket-chip--disabled) {
        cursor: pointer !important;
    }

    button.appointment-ticket-chip:not(.appointment-ticket-chip--disabled):hover {
        filter: brightness(1.05);
    }

    .appointment-ticket-legend {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        margin-top: 0.75rem;
        font-size: 0.75rem;
        color: #4b5563;
    }

    .appointment-ticket-legend-dot {
        display: inline-block;
        width: 0.75rem;
        height: 0.75rem;
        border-radius: 0.125rem;
        margin-right: 0.375rem;
    }

    .appointment-ticket-legend-dot--available { background-color: #22c55e; }
    .appointment-ticket-legend-dot--taken { background-color: #ef4444; }
</style>
