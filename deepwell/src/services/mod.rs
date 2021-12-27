/*
 * services/mod.rs
 *
 * DEEPWELL - Wikijump API provider and database manager
 * Copyright (C) 2021 Wikijump Team
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

//! The "services" module, providing low-level logical operations.
//!
//! Each service is named for a particular object or concept, and
//! provides several low-level methods for interacting with it.
//! This may be CRUD, or small operations which should be composed
//! into larger ones.
//!
//! As such, _all methods here are not contained in transactions,_
//! the expectation is that the caller will use transactions when needed.
//! For methods which make multiple calls, they will assert that they
//! are currently in a transaction, if you are not then they will raise
//! an error.

mod prelude {
    pub use super::base::BaseService;
    pub use super::error::*;
    pub use crate::utils::now;
    pub use crate::web::{ItemReference, ProvidedValue};
    pub use sea_orm::{
        ActiveModelTrait, ColumnTrait, Condition, ConnectionTrait, EntityTrait,
        QueryFilter, Set,
    };
}

mod base;
mod error;

pub mod page;
pub mod user;

use self::base::BaseService;
use self::page::PageService;
use self::user::UserService;
use crate::api::ApiRequest;
use sea_orm::{DatabaseConnection, DatabaseTransaction};

pub use self::error::*;

/// Extension trait to retrieve service objects from an `ApiRequest`.
pub trait RequestFetchService {
    fn database(&self) -> &DatabaseConnection;

    fn page<'txn>(&self, txn: &'txn DatabaseTransaction) -> PageService<'txn>;
    fn user<'txn>(&self, txn: &'txn DatabaseTransaction) -> UserService<'txn>;
}

impl RequestFetchService for ApiRequest {
    // Getters
    #[inline]
    fn database(&self) -> &DatabaseConnection {
        &self.state().database
    }

    // Service builders
    #[inline]
    fn page<'txn>(&self, txn: &'txn DatabaseTransaction) -> PageService<'txn> {
        PageService(BaseService::new(self, txn))
    }

    #[inline]
    fn user<'txn>(&self, txn: &'txn DatabaseTransaction) -> UserService<'txn> {
        UserService(BaseService::new(self, txn))
    }
}
