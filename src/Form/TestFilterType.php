<?php

namespace App\Form;

use App\Entity\TestFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('suiteUuid')
            ->add('cluster')
            ->add('testList')
            ->add('testingLevel')
            ->add('projectName')
            ->add('chip', null, array('required' => false))
            ->add('platform', null, array('required' => false))
            ->add('executionMode', null, array('required' => false))
            ->add('branchName', null, array('required' => false))
//            ->add('enabled')
            ->add('description')
            ->add('defectUrl')
//            ->add('createdAt')
//            ->add('updatedAt')
            ->add('issueContact', null, array('required' => false))
//            ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TestFilter::class,
        ]);
    }
}
