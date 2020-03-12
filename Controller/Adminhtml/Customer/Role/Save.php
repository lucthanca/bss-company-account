<?php

namespace Bss\CompanyAccount\Controller\Adminhtml\Customer\Role;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Bss\CompanyAccount\Api\Data\SubRoleInterface as Role;

class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Bss_CompanyAccount::config_section';

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Bss\CompanyAccount\Api\SubRoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var \Bss\CompanyAccount\Api\Data\SubRoleInterfaceFactory
     */
    private $roleFactory;

    /**
     * Save constructor.
     *
     * @param Action\Context $context
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Bss\CompanyAccount\Api\SubRoleRepositoryInterface $roleRepository
     * @param \Bss\CompanyAccount\Api\Data\SubRoleInterfaceFactory $roleFactory
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Action\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Bss\CompanyAccount\Api\SubRoleRepositoryInterface $roleRepository,
        \Bss\CompanyAccount\Api\Data\SubRoleInterfaceFactory $roleFactory,
        LoggerInterface $logger,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->customerRepository = $customerRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->roleRepository = $roleRepository;
        $this->roleFactory = $roleFactory;
    }

    /**
     * Save customer address action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $customerId = $this->getRequest()->getParam('customer_id', false);
        $roleId = $this->getRequest()->getParam('role_id', "");

        $error = false;
        try {
            /** @var \Bss\CompanyAccount\Api\Data\SubRoleInterface $role */
            $role = $this->roleFactory->create();
            $role->setRoleName($this->getRequest()->getParam(Role::NAME));
            $role->setRoleType(implode(',', $this->getRequest()->getParam(Role::TYPE)));
            $role->setMaxOrderPerDay($this->getRequest()->getParam(Role::MAX_ORDER_PER_DAY));
            $role->setMaxOrderAmount($this->getRequest()->getParam(Role::MAX_ORDER_AMOUNT));
            $role->setCompanyAccount($customerId);

            if (!empty($roleId)) {
                $role->setRoleId((int)$roleId);
                $message = __('Role has been updated.');
            } else {
                $role->setRoleId(null);
                $message = __('New role has been added.');
            }

            $this->roleRepository->save($role);

        } catch (\Exception $e) {
            $error = true;
            $message = __('We can\'t change role right now.');
            $this->logger->critical($e);
        }

        $roleId = empty($roleId) ? null : $roleId;
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            [
                'message' => $message,
                'error' => $error,
                'data' => [
                    'role_id' => $roleId
                ]
            ]
        );

        return $resultJson;
    }
}