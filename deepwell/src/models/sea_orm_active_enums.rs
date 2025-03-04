//! SeaORM Entity. Generated by sea-orm-codegen 0.6.0

use sea_orm::entity::prelude::*;

#[derive(
    Serialize, Deserialize, Debug, Copy, Clone, PartialEq, EnumIter, DeriveActiveEnum,
)]
#[sea_orm(rs_type = "String", db_type = "Enum", enum_name = "revision_type")]
#[serde(rename_all = "camelCase")]
pub enum RevisionType {
    #[sea_orm(string_value = "create")]
    Create,
    #[sea_orm(string_value = "delete")]
    Delete,
    #[sea_orm(string_value = "regular")]
    Regular,
    #[sea_orm(string_value = "undelete")]
    Undelete,
}
