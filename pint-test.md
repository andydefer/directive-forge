# Pint Formatting Test Report
*Generated: mer. 10 juin 2026 20:03:32 WAT*


  ⨯..............................⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯........⨯..⨯......⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel  
    FAIL   ................................................................................................................................................. 96 files, 50 style issues  
  ⨯ app/Actions/Api/V1/Users/ShowAction.php                               single_line_after_imports, blank_line_after_namespace, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Actions/User/ProfileAction.php                                    single_line_after_imports, blank_line_after_namespace, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Actions/User/ShowAction.php                                       single_line_after_imports, blank_line_after_namespace, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Collections/Admin/UserCollection.php                                                                                                                  single_blank_line_at_eof  
  ⨯ app/Collections/OrderDataCollection.php                                                                                                                   single_blank_line_at_eof  
  ⨯ app/Collections/ProductCollection.php                                                                                                                     single_blank_line_at_eof  
  ⨯ app/Collections/UserCollection.php                                                                                                                        single_blank_line_at_eof  
  ⨯ app/Configs/Database/MysqlConfig.php                                                                                                                      single_blank_line_at_eof  
  ⨯ app/Configs/DatabaseConfig.php                                                                                                                            single_blank_line_at_eof  
  ⨯ app/Data/Api/UserData.php                                                           braces_position, single_line_empty_body, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Data/OrderData.php                                                              braces_position, single_line_empty_body, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Data/ProductData.php                                                            braces_position, single_line_empty_body, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Data/UserData.php                                                               braces_position, single_line_empty_body, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Directives/CacheClearDirective.php                                      new_with_parentheses, single_line_after_imports, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Directives/TestCmdDirective.php                                         new_with_parentheses, single_line_after_imports, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Directives/User/Domain/HelloDirective.php                               new_with_parentheses, single_line_after_imports, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Directives/UserList2Directive.php                                       new_with_parentheses, single_line_after_imports, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Directives/UserListDirective.php                                        new_with_parentheses, single_line_after_imports, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Http/Requests/Api/V1/StoreUserRequest.php                                                                                       new_with_parentheses, single_blank_line_at_eof  
  ⨯ app/Http/Requests/LoginRequest.php                                                                                                  new_with_parentheses, single_blank_line_at_eof  
  ⨯ app/Http/Requests/StoreUserRequest.php                                                                                              new_with_parentheses, single_blank_line_at_eof  
  ⨯ app/Records/Api/UserDataRecord.php                                                                               braces_position, single_line_empty_body, single_blank_line_at_eof  
  ⨯ app/Records/OrderRecord.php                                                                                      braces_position, single_line_empty_body, single_blank_line_at_eof  
  ⨯ app/Records/ProductDataRecord.php                                                                                braces_position, single_line_empty_body, single_blank_line_at_eof  
  ⨯ app/Records/UserDataRecord.php                                                                                   braces_position, single_line_empty_body, single_blank_line_at_eof  
  ⨯ app/Repositories/Admin/UserRepository.php      concat_space, not_operator_with_successor_space, blank_line_before_statement, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Repositories/ProductRepository.php         concat_space, not_operator_with_successor_space, blank_line_before_statement, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Repositories/UserRepository.php            concat_space, not_operator_with_successor_space, blank_line_before_statement, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Services/Api/PaymentProcessorService.php                                                                                                              single_blank_line_at_eof  
  ⨯ app/Services/NotificationSenderService.php                                                                                                                single_blank_line_at_eof  
  ⨯ app/Services/PaymentProcessorService.php                                                                                                                  single_blank_line_at_eof  
  ⨯ app/Tasks/ProcessOrderTask.php                                                            not_operator_with_successor_space, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Tasks/SendWelcomeEmailTask.php                                                        not_operator_with_successor_space, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/Tasks/User/SendWelcomeEmailTask.php                                                   not_operator_with_successor_space, no_whitespace_in_blank_line, single_blank_line_at_eof  
  ⨯ app/ValueObjects/EmailAddressVO.php                                                                                                                       single_blank_line_at_eof  
  ⨯ app/ValueObjects/User/EmailAddressVO.php                                                                                                                  single_blank_line_at_eof  
  ⨯ src/DirectiveForgeServiceProvider.php                                                                                                                                 concat_space  
  ⨯ tests/Unit/Directives/MakeActionDirectiveTest.php                                                                                                                     concat_space  
  ⨯ tests/Unit/Directives/MakeConfigDirectiveTest.php                                                                                                                     concat_space  
  ⨯ tests/Unit/Directives/MakeDataDirectiveTest.php                                                                                                                       concat_space  
  ⨯ tests/Unit/Directives/MakeDirectiveTest.php                                                                                                                           concat_space  
  ⨯ tests/Unit/Directives/MakeRecordDirectiveTest.php                                                                                                                     concat_space  
  ⨯ tests/Unit/Directives/MakeRepositoryDirectiveTest.php                                                                                                                 concat_space  
  ⨯ tests/Unit/Directives/MakeRequestDirectiveTest.php                                                                                                                    concat_space  
  ⨯ tests/Unit/Directives/MakeServiceDirectiveTest.php                                                                                                                    concat_space  
  ⨯ tests/Unit/Directives/MakeTaskDirectiveTest.php                                                                                                                       concat_space  
  ⨯ tests/Unit/Directives/MakeTypedCollectionDirectiveTest.php                                                                                                            concat_space  
  ⨯ tests/Unit/Directives/MakeValueObjectDirectiveTest.php                                                                                                                concat_space  
  ⨯ tests/Unit/Generators/DirectiveGeneratorTest.php                                                                                                              new_with_parentheses  
  ⨯ tests/Unit/Generators/FileCreationTest.php                                                                                                         concat_space, no_unused_imports  

