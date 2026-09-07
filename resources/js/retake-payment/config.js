import { baseUri } from "../app";

export const CONFIG = {
    REGISTRATIONS_API: baseUri('retake-registrations'),
    PAYMENT_BATCHES_API: baseUri('payment-batches'),
    TERMS_API: baseUri('retake-terms'),
    EXAM_TYPES_API: baseUri('exam-types'),
    DEBOUNCE_DELAY: 300,
    LOCALE: 'en-GB',
    PER_PAGE: 15,
};
