/*
 * includes/parse.rs
 *
 * ftml - Library to parse Wikidot text
 * Copyright (C) 2019-2022 Wikijump Team
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

use super::IncludeRef;
use crate::data::{PageRef, PageRefParseError};
use pest::iterators::Pairs;
use pest::Parser;
use std::borrow::Cow;
use std::collections::HashMap;

#[derive(Parser, Debug)]
#[grammar = "includes/grammar.pest"]
struct IncludeParser;

pub fn parse_include_block(
    text: &str,
    start: usize,
) -> Result<(IncludeRef, usize), IncludeParseError> {
    match IncludeParser::parse(Rule::include, text) {
        Ok(mut pairs) => {
            // Extract inner pairs
            // These actually make up the include block's tokens
            let first = pairs.next().expect("No pairs returned on successful parse");
            let span = first.as_span();

            info!("Parsed include block");

            // Convert into an IncludeRef
            let include = process_pairs(first.into_inner())?;

            // Adjust offset and return
            Ok((include, start + span.end()))
        }
        Err(error) => {
            warn!("Include block was invalid: {error}");
            Err(IncludeParseError)
        }
    }
}

fn process_pairs(mut pairs: Pairs<Rule>) -> Result<IncludeRef, IncludeParseError> {
    let page_raw = pairs.next().ok_or(IncludeParseError)?.as_str();
    let page_ref = PageRef::parse(page_raw)?;

    debug!("Got page for include {page_ref:?}");
    let mut arguments = HashMap::new();
    let mut var_reference = String::new();

    for pair in pairs {
        debug_assert_eq!(pair.as_rule(), Rule::argument);

        let (key, value) = {
            let mut argument_pairs = pair.into_inner();

            let key = argument_pairs
                .next()
                .expect("Argument pairs terminated early")
                .as_str();

            let value = argument_pairs
                .next()
                .expect("Argument pairs terminated early")
                .as_str();

            (key, value)
        };

        trace!("Adding argument for include (key '{key}', value '{value}')");

        // In Wikidot, the first argument takes precedence.
        //
        // However, with nested includes, you can set a fallback
        // by making the first argument its corresponding value.
        //
        // For instance, if we're in `component:test`:
        // ```
        // [[include component:test-backend
        //     width={$width} |
        //     width=300px
        // ]]
        // ```

        var_reference.clear();
        str_write!(var_reference, "{{${key}}}");

        if !arguments.contains_key(key) && value != var_reference {
            let key = Cow::Borrowed(key);
            let value = Cow::Borrowed(value);

            arguments.insert(key, value);
        }
    }

    Ok(IncludeRef::new(page_ref, arguments))
}

#[derive(Debug, PartialEq, Eq)]
pub struct IncludeParseError;

impl From<PageRefParseError> for IncludeParseError {
    #[inline]
    fn from(_: PageRefParseError) -> Self {
        IncludeParseError
    }
}
