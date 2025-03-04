/*
 * services/job/mod.rs
 *
 * DEEPWELL - Wikijump API provider and database manager
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

//! This service runs asynchronous jobs in the background using an in-memory queue.
//!
//! At present we do not use a separate service which stores jobs durably. This
//! can mean that if this DEEPWELL node fails, the queued jobs will not be run.
//!
//! The following kinds of jobs are available:
//! * Rerendering a page

mod prelude {
    pub use super::super::prelude::*;
    pub use super::structs::*;
}

mod service;
mod structs;

pub use self::service::{JobRunner, JobService};
pub use self::structs::*;
