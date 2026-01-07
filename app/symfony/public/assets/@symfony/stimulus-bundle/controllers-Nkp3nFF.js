import controller_0 from "../ux-turbo/turbo_controller.js";
import controller_1 from "../../controllers/flash_controller.js";
import controller_2 from "../../controllers/animation_controller.js";
import controller_3 from "../../controllers/hello_controller.js";
import controller_4 from "../../controllers/quiz_controller.js";
import controller_5 from "../../controllers/stripe_controller.js";
export const eagerControllers = {"symfony--ux-turbo--turbo-core": controller_0, "flash": controller_1, "animation": controller_2, "hello": controller_3, "quiz": controller_4, "stripe": controller_5};
export const lazyControllers = {"csrf-protection": () => import("../../controllers/csrf_protection_controller.js")};
export const isApplicationDebug = true;