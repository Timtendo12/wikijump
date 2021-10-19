import { scrollElement } from "@wikijump/util"

export type Point = [x: number, y: number]
export type Direction = "up" | "down" | "left" | "right"
export type Axis = "vertical" | "horizontal"
export type GestureType = "start" | "move" | "end" | "cancel"

/** Represents the state of an active gesture. */
export interface Gesture {
  start: Point
  diff: Point
  diffAbs: Point
  direction: Direction
  dist: number
  type: GestureType
}

const DIRECTIONS = ["up", "down", "left", "right"] as const

/** Resolves a {@link Gesture} from two points and the current touch type. */
function resolve([x1, y1]: Point, [x2, y2]: Point, type: GestureType): Gesture {
  // with these vars: 0 is vertical, 1 is horizontal
  const diff: Point = [y1 - y2, x1 - x2]
  const diffAbs: Point = [Math.abs(diff[0]), Math.abs(diff[1])]
  const axis = diffAbs[1] > diffAbs[0] ? 1 : 0
  const dist = diffAbs[axis]
  const direction = DIRECTIONS[axis * 2 + +(diff[axis] < 0)]
  //                               ^      ^        ^  dir via sign (+ up|left, - down|right)
  //                               ^      ^  convert boolean to integer
  //                               ^ this is either 0 or 2, as axis is either 0 or 1

  return { start: [x1, y1], diff, diffAbs, direction, dist, type }
}

/**
 * Converts a {@link Direction} to an {@link Axis}.
 *
 * @param direction - The direction to convert.
 */
export function directionToAxis(direction: Direction): Axis {
  return direction === "up" || direction === "down" ? "vertical" : "horizontal"
}

/**
 * Starts observing an element for gestures. Repeatedly calls a handler
 * callback with gesture information. Returns a `destroy` function, which
 * when called will disable observation.
 *
 * @param target - The element to observe.
 * @param handler - The callback to call with gesture information.
 */
export function gestureObserve(target: HTMLElement, handler: (gesture: Gesture) => void) {
  // this var tracks the current gesture, but also indicates if one is even running,
  // as it is set to `null` when no gesture is active
  let id: number | null = null
  let start: Point | null = null

  const cancel = () => {
    id = null
    start = null
    rmEvtlistener(document, ["touchmove", "touchend", "touchcancel"], wrapper)
  }

  const wrapper = (evt: TouchEvent) => {
    let touch!: Touch
    // if running a gesture and the ID of the event doesn't match ours, ignore this event
    if (id !== null) {
      for (const idx of Array.from(evt.changedTouches)) {
        if (idx.identifier === id) {
          touch = idx
          break
        }
      }
      if (!touch) return
    }

    // init. and start gesture recognition
    if (evt.type === "touchstart") {
      evtlistener(document, ["touchmove", "touchend", "touchcancel"], wrapper, {
        passive: true
      })
      touch = evt.changedTouches[0]
      id = touch.identifier
      start = [touch.clientX, touch.clientY]
    }

    // gesture running
    if (id !== null && start) {
      let type!: GestureType
      // prettier-ignore
      switch (evt.type) {
        case 'touchstart':  type = 'start';  break
        case 'touchmove':   type = 'move';   break
        case 'touchend':    type = 'end';    break
        case 'touchcancel': type = 'cancel'; break
      }

      const gesture = resolve(start, [touch.clientX, touch.clientY], type)

      if (type !== "start") {
        const target = evt.target as HTMLElement
        const axis = directionToAxis(gesture.direction)
        // if we found a scrollable element in the direction of our gesture, cancel
        if (scrollElement(target, axis)) cancel()
      }

      // check if we're performing a "scrolling" gesture
      // on an element that is scrollable

      handler(gesture)

      if (type === "end" || type === "cancel") cancel()
    }
  }

  target.addEventListener("touchstart", wrapper, { passive: true })

  return () => target.removeEventListener("touchstart", wrapper)
}

/** Helper function for creating event listeners. */
function evtlistener(
  target: Node,
  events: string[],
  fn: AnyFunction,
  opts: AddEventListenerOptions = {}
) {
  events.forEach(event => {
    target.addEventListener(event, fn, opts)
  })
}

/** Helper function for removing event listeners. */
function rmEvtlistener(
  target: Node,
  events: string[],
  fn: AnyFunction,
  opts: AddEventListenerOptions = {}
) {
  events.forEach(event => {
    target.removeEventListener(event, fn, opts)
  })
}
