/*
 * render/text/elements.rs
 *
 * ftml - Library to parse Wikidot text
 * Copyright (C) 2019-2021 Wikijump Team
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

//! Module that implements text rendering for `Element` and its children.

use super::TextContext;
use crate::render::ModuleRenderMode;
use crate::tree::{Element, ListItem, ListType};

pub fn render_elements(log: &slog::Logger, ctx: &mut TextContext, elements: &[Element]) {
    debug!(log, "Rendering elements"; "elements-len" => elements.len());

    for element in elements {
        render_element(log, ctx, element);
    }
}

pub fn render_element(log: &slog::Logger, ctx: &mut TextContext, element: &Element) {
    debug!(log, "Rendering element"; "element" => element.name());

    match element {
        Element::Container(container) => render_elements(log, ctx, container.elements()),
        Element::Module(module) => {
            ctx.handle()
                .render_module(log, ctx.buffer(), module, ModuleRenderMode::Text)
        }
        Element::Text(text) | Element::Raw(text) | Element::Email(text) => {
            ctx.push_str(text)
        }
        Element::Anchor { elements, attributes, .. } => {
            render_elements(log, ctx, elements);

            if let Some(href) = attributes.get().get("href") {
                str_write!(ctx, " [{}]", href);
            }
        }
        Element::Link { url, label, .. } => {
            ctx.handle().get_link_label(log, url, label, |label| {
                str_write!(ctx, "{} [{}]", label, url);
            });
        }
        Element::List { ltype, items } => {
            for item in items {
                match item {
                    ListItem::Elements(elements) => {
                        // Render bullet and its depth
                        let depth = ctx.list_depth();
                        for _ in 0..depth {
                            ctx.push(' ');
                        }

                        match *ltype {
                            ListType::Bullet => ctx.push_str("* "),
                            ListType::Numbered => {
                                let index = ctx.next_list_index();
                                str_write!(ctx, "{}. ", index);
                            }
                            ListType::Generic => (),
                        }

                        // Render elements for this list item
                        render_elements(log, ctx, elements);
                    }
                    ListItem::SubList(list) => {
                        // Update bullet depth
                        ctx.incr_list_depth();
                        render_element(log, ctx, list);
                        ctx.decr_list_depth();
                    }
                }
            }
        }
        Element::RadioButton { checked, .. } => {
            str_write!(ctx, "({})", if *checked { '*' } else { ' ' })
        }
        Element::CheckBox { checked, .. } => {
            str_write!(ctx, "[{}]", if *checked { 'X' } else { ' ' })
        }
        Element::Collapsible {
            elements,
            show_text,
            hide_text,
            show_top,
            show_bottom,
            ..
        } => {
            macro_rules! get_text {
                ($input:expr, $message:expr) => {
                    match $input {
                        Some(ref text) => &text,
                        None => {
                            let locale = &ctx.info().locale;

                            ctx.handle().get_message(log, locale, $message)
                        }
                    }
                };
            }

            let show_text = get_text!(show_text, "collapsible-open");
            let hide_text = get_text!(hide_text, "collapsible-hide");

            // Top of collapsible
            str_write!(ctx, "\n{}\n", show_text);

            if *show_top {
                str_write!(ctx, "{}\n", hide_text);
            }

            // Collapsible contents
            render_elements(log, ctx, elements);

            // Bottom of collapsible
            if *show_bottom {
                str_write!(ctx, "{}\n", hide_text);
            }
        }
        Element::Color { elements, .. } => render_elements(log, ctx, elements),
        Element::Code { contents, language } => {
            let language = match language {
                Some(cow) => &cow,
                None => "",
            };

            str_write!(ctx, "```{}\n{}\n```", language, contents);
        }
        Element::Html { contents } => {
            str_write!(ctx, "```html\n{}\n```", contents);
        }
        Element::Iframe { url, .. } => str_write!(ctx, "iframe: {}", url),
        Element::LineBreak => ctx.push('\n'),
        Element::LineBreaks(amount) => ctx.push_multiple('\n', amount.get()),
        Element::HorizontalRule => {
            // Add a newline if the last character wasn't a newline
            match ctx.buffer().chars().next_back() {
                Some('\n') | None => (),
                _ => ctx.push('\n'),
            }

            ctx.push_str("\n-----\n\n");
        }
    }
}
