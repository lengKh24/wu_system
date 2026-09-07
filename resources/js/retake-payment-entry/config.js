import { baseUri } from "../app";

export const CONFIG = {
    PAYMENT_BATCHES_API: baseUri('payment-batches'),
    PAYMENT_ENTRIES_API: baseUri('payment-entries'),
    PER_PAGE: 15,
};
