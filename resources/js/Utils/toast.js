// utils/toast.js
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

export const showToast = (message, tipo = "info") => {
    const config = {
        position: toast.POSITION.TOP_RIGHT,
        autoClose: 3000,
        hideProgressBar: false,
        closeOnClick: true,
        pauseOnHover: true,
        draggable: false,
        theme: "colored",
    };

    switch (tipo.toLowerCase()) {
        case "success":
            toast.success(message, config);
            break;
        case "error":
            toast.error(message, config);
            break;
        case "warning":
            toast.warning(message, config);
            break;
        case "info":
        default:
            toast.info(message, config);
            break;
    }
};
